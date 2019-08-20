<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;

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
      $tokens = $auth_service->generateAccessToken($_GET['code']);
      if (is_object($tokens)) {
        \Drupal::service('messenger')->addMessage(t('You get Authorization on your Zoho CRM.'), 'status');
      }
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
