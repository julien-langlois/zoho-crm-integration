<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystem;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use zcrmsdk\oauth\ZohoOAuth;

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
   * Drupal Scopes Services.
   *
   * @var \Drupal\zoho_crm_integration\Service\ZohoCRMIntegrationScopesService
   */
  protected $scopesService;

  /**
   * The custom Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ZohoCRMAuthService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal Config Factory service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   Drupal File System service.
   * @param \Drupal\zoho_crm_integration\Service\ZohoCRMIntegrationScopesService $scopes_service
   *   Drupal Scopes services.
   * @param \GuzzleHttp\Client $http_client
   *   Drupal HTTP Client service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Drupal URL service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Custom ZohoCRM module Logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system, ZohoCRMIntegrationScopesService $scopes_service, Client $http_client, UrlGeneratorInterface $url_generator, LoggerInterface $logger) {
    global $base_url;

    // Getting services.
    $this->scopesService = $scopes_service;
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->refreshToken = NULL;
    $this->urlGenerator = $url_generator;
    $this->logger = $logger;

    // Getting the saved refresh token.
    if ($refresh_token = $this->configFactory->get(self::SETTINGS)->get('refresh_token')) {
      $this->refreshToken = $refresh_token;
    }

    $this->scope = $scopes_service->getScopesParameters();
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
      $this->logger->alert("Error trying revoke Zoho CRM API Refresh Token. Exception message: {$e->getMessage()}");
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
      $this->logger->alert("Error trying generate Zoho CRM API Access Token from Grant Token. Exception message: {$e->getMessage()}");
    }
  }

  /**
   * Check if is possible to connect on API creating a Lead.
   *
   * @return bool
   *   Return TRUE if you could success connect on user/info endpoint.
   *
   * @throws \zcrmsdk\oauth\exception\ZohoOAuthException
   */
  public function checkConnection() {
    $oauth_client = ZohoOAuth::getClientInstance();

    try {
      $accessToken = $oauth_client->getAccessToken($this->userEmail);
      $user = $oauth_client->getUserEmailIdFromIAM($accessToken);

      return ($user !== NULL);
    }
    catch (ZohoOAuthException $e) {
      $this->logger->alert("Error trying test Zoho CRM API connection. Exception message: {$e->getMessage()}");
      return FALSE;
    }
  }

}
