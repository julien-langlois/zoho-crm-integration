<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystem;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
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
   * Constructs a new ZohoCRMAuthService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system) {
    global $base_url;

    $scopes = [
      'ZohoCRM.users.ALL',
      'ZohoCRM.modules.ALL',
      'Aaaserver.profile.Read',
      'ZohoCRM.settings.ALL',
      'ZohoCRM.bulk.ALL',
    ];

    $this->configFactory = $config_factory;
    $this->scope = implode(",", $scopes);
    $this->clientId = $config_factory->get(self::SETTINGS)->get('client_id');
    $this->clientSecret = $config_factory->get(self::SETTINGS)->get('client_secret');
    $this->userEmail = $config_factory->get(self::SETTINGS)->get('current_user_email');
    $this->zohoDomain = $config_factory->get(self::SETTINGS)->get('zoho_domain');
    $this->redirectUrl = $base_url . Url::fromRoute(self::ROUTE)->toString();
    $this->fileSystem = $file_system->realPath('private://');

    // Initialize the Client Service.
    ZCRMRestClient::initialize($this->getAuthorizationParams());
    $this->grantUrl = ZohoOAuth::getGrantURL();
    $this->revokeUrl = ZohoOAuth::getRevokeTokenURL();
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
   * @return string
   *   The revoke URL.
   */
  public function getRevokeUrl() {
    $refresh_token = '';
    return "{$this->revokeUrl}?token={$refresh_token}";
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
    ];

    if (!empty($this->zohoDomain) && $this->zohoDomain != 'default') {
      $params['accounts_url'] = $this->zohoDomain;
    }

    return $params;
  }

  /**
   * Generate access token.
   *
   * @param string $grant_token
   *   Grant token.
   *
   * @return object
   *   The Tokens Object.
   *
   * @throws \zcrmsdk\oauth\exception\ZohoOAuthException
   */
  public function generateAccessToken($grant_token) {
    $oauth_client = ZohoOAuth::getClientInstance();
    return $oauth_client->generateAccessToken($grant_token);
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
      // TODO: Add a log.
      return FALSE;
    }
  }

}
