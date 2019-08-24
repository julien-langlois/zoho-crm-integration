<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystem;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\exception\ZCRMException;

/**
 * Class ZohoCRMAuthService.
 */
class ZohoCRMAuthService implements ZohoCRMAuthInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal Config service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Authorization Redirect URL.
   *
   * @var string
   */
  protected $redirectUrl;

  /**
   * The list of authorized scopes.
   *
   * @var array
   */
  protected $scope;

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The client secret property.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * User e-mail.
   *
   * @var string
   */
  protected $userEmail;

  /**
   * Zoho domain.
   *
   * @var string
   */
  protected $zohoDomain;

  /**
   * Revoke URL.
   *
   * @var string
   */
  protected $revokeUrl;

  /**
   * Grant URL.
   *
   * @var string
   */
  protected $grantUrl;

  /**
   * File System Service.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The generated refresh token.
   *
   * @var string
   */
  protected $refreshToken;

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal URL service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new ZohoCRMAuthService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal Config Factory service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   Drupal File System service.
   * @param \GuzzleHttp\Client $http_client
   *   Drupal HTTP Client service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Drupal URL service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system, Client $http_client, UrlGeneratorInterface $url_generator) {
    global $base_url;

    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->refreshToken = NULL;
    $this->urlGenerator = $url_generator;

    // Getting the saved refresh token.
    if ($refresh_token = $this->configFactory->get(self::SETTINGS)->get('refresh_token')) {
      $this->refreshToken = $refresh_token;
    }

    $scopes = [
      'ZohoCRM.users.ALL',
      'ZohoCRM.modules.ALL',
      'Aaaserver.profile.Read',
      'ZohoCRM.settings.ALL',
      'ZohoCRM.bulk.ALL',
    ];

    $this->scope = implode(",", $scopes);
    $this->clientId = $config_factory->get(self::SETTINGS)->get('client_id');
    $this->clientSecret = $config_factory->get(self::SETTINGS)->get('client_secret');
    $this->userEmail = $config_factory->get(self::SETTINGS)->get('current_user_email');
    $this->zohoDomain = $config_factory->get(self::SETTINGS)->get('zoho_domain');
    $this->redirectUrl = $base_url . Url::fromRoute(self::ROUTE)->toString();
    $this->fileSystem = $file_system->realPath('private://');

    // Initialize the Client Service.
    if ($this->hasClientId() && !empty($this->refreshToken)) {
      ZCRMRestClient::initialize($this->getAuthorizationParams());
      $this->grantUrl = ZohoOAuth::getGrantURL();
      $this->revokeUrl = ZohoOAuth::getRevokeTokenURL();
    }
    else {
      $this->grantUrl = $this->zohoDomain . ZohoOAuth::getGrantURL();
      $this->revokeUrl = $this->zohoDomain . ZohoOAuth::getRevokeTokenURL();
    }
  }

  /**
   * Get magic method.
   *
   * @inheritDoc
   */
  public function __get($name) {
    return $this->$name;
  }

  /**
   * Build authorization URL.
   *
   * @return string
   *   Full authorization URL.
   */
  public function getAuthorizationUrl() {
    $params = [
      'prompt' => 'consent',
      'scope' => $this->scope,
      'client_id' => $this->clientId,
      'response_type' => 'code',
      'access_type' => 'offline',
      'redirect_uri' => $this->redirectUrl,
    ];
    $query_string = http_build_query($params);

    return "{$this->grantUrl}?{$query_string}";
  }

  /**
   * Generate the Revoke URL.
   *
   * @return bool
   *   Return TRUE if HTTP request works or FALSE.
   *
   * @throws \zcrmsdk\oauth\exception\ZohoOAuthException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function revokeRefreshToken() {
    try {
      $persistenceTokens = ZohoOAuth::getPersistenceHandlerInstance()->getOAuthTokens($this->userEmail);
      $refresh_token = $persistenceTokens->getRefreshToken();
      $request = $this->httpClient->request('POST', "{$this->revokeUrl}?token={$refresh_token}");

      if ($request->getStatusCode() == '200') {
        return TRUE;
      }
    }
    catch (GuzzleException $e) {
      return FALSE;
    }

    return FALSE;
  }

  /**
   * Check if Client ID exists.
   *
   * @return bool
   *   True if Client ID exists, otherwise false.
   */
  public function hasClientId() {
    return $this->clientId !== NULL && $this->clientId !== '';
  }

  /**
   * Get authorization parameters.
   *
   * @return array
   *   Authorization parameters.
   */
  public function getAuthorizationParams() {
    $params = [
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'redirect_uri' => $this->redirectUrl,
      'currentUserEmail' => $this->userEmail,
      'token_persistence_path' => $this->fileSystem,
      'accounts_url' => $this->zohoDomain,
    ];

    return $params;
  }

  /**
   * Generate access token.
   *
   * @param string $grant_token
   *   Grant token.
   *
   * @throws \zcrmsdk\oauth\exception\ZohoOAuthException
   */
  public function generateAccessToken($grant_token) {
    try {
      ZCRMRestClient::initialize($this->getAuthorizationParams());
      $oauth_client = ZohoOAuth::getClientInstance();
      $tokens = $oauth_client->generateAccessToken($grant_token);

      if (is_object($tokens)) {
        $this->refreshToken = $tokens->getRefreshToken();
        $this->configFactory->getEditable(self::SETTINGS)->set('refresh_token', $this->refreshToken)->save();

        $response = new RedirectResponse($this->urlGenerator->generateFromRoute('zoho_crm_integration.settings'));
        $response->send();
      }
    }
    catch (ZohoOAuthException $e) {
      // TODO: Write a log.
    }
  }

  /**
   * Check if is possible to connect on API creating a Lead.
   *
   * @return bool
   *   Return if was possible to connect or not.
   */
  public function checkConnection() {
    $clientIns = ZCRMRestClient::getInstance();
    try {
      $moduleIns = $clientIns->getModuleInstance("Leads");
      $record = ZCRMRecord::getInstance("Leads", NULL);
      $record->setFieldValue('First_Name', 'Zoho');
      $record->setFieldValue('Last_Name', 'CRM Integration');
      $record->setFieldValue('Company', 'AT');
      $record->setFieldValue('Designation', 'Zoho CRM Integration connection test.');

      $bulkAPIResponse = $moduleIns->createRecords([$record]);
      $entityResponses = $bulkAPIResponse->getEntityResponses();
      $entityResponse = $entityResponses[0];
      $status = $entityResponse->getStatus();

      // Delete the test Lead.
      $createdRecordInstance = $entityResponse->getData();
      $recordIds = [$createdRecordInstance->getEntityId()];
      $moduleIns->deleteRecords($recordIds);

      return ($status == 'success');
    }
    catch (ZCRMException $e) {
      // TODO: Add a drupal log.
      return FALSE;
    }
  }

}
