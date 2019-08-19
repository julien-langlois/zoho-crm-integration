<?php

namespace Drupal\zoho_crm_integration\Zoho;

/**
 * Provide oAuth connection methods and helpers.
 */
class ZohoCRMAuth {

  /**
   * Zoho settings form configs.
   */
  private $config;

  /**
   * ZohoCRMAuth constructor.
   */
  public function __construct() {
    // ToDo: Use service or dependency injection.
    $this->config = \Drupal::config('zoho_crm_integration.settings');
  }

  /**
   * Return a authorization URL.
   */
  public static function authorizationUrl() {
    $scope = (new ZohoCRMAuth)->getScope();
    $client_id = (new ZohoCRMAuth)->getClientId();
    // ToDo: Use the Client class method.
    $redirect_uri = 'http://d8.l/admin/config/services/zoho-crm-integration';
    $url = "https://accounts.zoho.com/oauth/v2/auth?scope={$scope}&client_id={$client_id}&response_type=code&access_type=offline&redirect_uri={$redirect_uri}";

    return $url;
  }

  /**
   * Make a POST to accounts URL and get the Access and Refresh Tokens.
   */
  protected function generateAccessToken() {
//    $configuration = array("client_id"=>{client_id},"client_secret"=>{client_secret},"redirect_uri"=>{redirect_url},"currentUserEmail"=>{user_email_id},"token_persistence_path" => "/");
//    ZCRMRestClient::initialize($configuration);
//    $oAuthClient = ZohoOAuth::getClientInstance();
//    $grantToken = "paste_the_self_authorized_grant_token_here";
//    $oAuthTokens = $oAuthClient->generateAccessToken($grantToken);

    // ToDO: Make a POST to URL.
    //https://accounts.zoho.com/oauth/v2/token?code={$code}&redirect_uri={$redirect}&client_id={$client_id}&client_secret={$secret}&grant_type=authorization_code
  }

  /**
   * Return a list of scopes.
   *
   * @return array
   *   A array of all scopes selected.
   */
  protected function getScope() {
    $scopes = ['ZohoCRM.users.ALL', 'ZohoCRM.modules.ALL'];
    return implode(",", $scopes);
  }

  /**
   * Check if the Client ID exist.
   *
   * @return bool
   *   Return TRUE if found a Client ID value.
   */
  public function hasClientId() {
    return ($this->config->get('client_id') != NULL && $this->config->get('client_id') != "");
  }

  /**
   * Get the client ID value.
   *
   * @return string
   *   The Client ID value.
   */
  public function getClientId() {
    return $this->config->get('client_id');
  }

}
