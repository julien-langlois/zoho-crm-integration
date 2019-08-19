<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Class ZohoCRMIntegrationClientService.
 */
class ZohoCRMIntegrationClientService implements ZohoCRMIntegrationClientServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ZohoCRMIntegrationClientService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get Zoho CRM settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *  Zoho CRM settings.
   */
  public function getConfig() {
    return $this->configFactory->get('zoho_crm_integration.zoho_crm_integration_client_service');
  }

  /**
   * Get Redirect URL.
   *
   * @return String
   *  Redirect URL.
   */
  public static function getRedirectUrl() {
    global $base_url;

    return $base_url . Url::fromRoute('zoho_crm_integration.settings')->toString();
  }

}
