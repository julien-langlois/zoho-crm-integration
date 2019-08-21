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
   * @var \Drupal\Core\Config\ImmutableConfig Config.
   */
  protected $config;

  /**
   * @var Redirect URL.
   */
  protected $redirectUrl;

  /**
   * @var Scope.
   */
  protected $scope;

  /**
   * @var Client ID.
   */
  protected $clientId;

  /**
   * The client secret property.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * @var Secret ID.
   */
  protected $secretId;

  /**
   * @var User e-mail.
   */
  protected $userEmail;

  /**
   * @var \Drupal\zoho_crm_integration\FileSystem File system.
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
    $this->redirectUrl = $base_url . Url::fromRoute(self::ROUTE)->toString();
    $this->fileSystem = $file_system->realPath('private://');

    // Initialize the Client Service.
    ZCRMRestClient::initialize($this->getAuthorizationParams());
  }

  /**
   * Build authorization URL.
   *
   * @return string
   *  Full authorization URL.
   */
  public function getAuthorizationUrl() {
    // @TODO: refactor to use absolute URL/query parameters properly.
    return "https://accounts.zoho.com/oauth/v2/auth?prompt=consent&scope={$this->scope}&client_id={$this->clientId}&response_type=code&access_type=offline&redirect_uri={$this->redirectUrl}";
  }

  public function __get($name) {
    return $this->$name;
  }

  /**
   * Check if Client ID exists.
   *
   * @return bool
   *  True if Client ID exists, otherwise false.
   */
  public function hasClientId() {
    return $this->clientId !== NULL && $this->clientId !== '';
  }

  /**
   * Get authorization parameters.
   *
   * @return array
   *  Authorization parameters.
   */
  public function getAuthorizationParams() {
    return [
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'redirect_uri' => $this->redirectUrl,
      'currentUserEmail' => $this->userEmail,
      'token_persistence_path' => $this->fileSystem,
    ];
  }

  /**
   * Generate access token.
   *
   * @param $grant_token
   *  Grant token.
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
