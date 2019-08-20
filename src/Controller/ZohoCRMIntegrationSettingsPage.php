<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zoho_crm_integration\Zoho\ZohoCRMAuth;

/**
 * Module settings page controller.
 */
class ZohoCRMIntegrationSettingsPage extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    // Retrieve form.
    $form = $this->formBuilder()->getForm('Drupal\zoho_crm_integration\Form\ZohoCRMIntegrationForm');

    // Retrieve authentication service.
    $auth_service = \Drupal::service('zoho_crm_integration.auth');
    $auth_url = $auth_service->getAuthorizationUrl();

    // Get client ID.
    if (isset($_GET['code'])) {
      $auth_service->generateAccessToken($_GET['code']);
    }

    $build = [
      '#theme' => 'zoho_crm_integration__settings_page',
      '#form' => $form,
      '#status' => 0,
      '#auth_url' => $auth_url,
    ];

    return $build;
  }

}
