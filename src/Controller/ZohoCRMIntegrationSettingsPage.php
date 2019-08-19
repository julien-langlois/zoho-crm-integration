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
    $form = $this->formBuilder()->getForm('Drupal\zoho_crm_integration\Form\ZohoCRMIntegrationForm');

    $build = [
      '#theme' => 'zoho_crm_integration__settings_page',
      '#form' => $form,
      '#status' => 0,
    ];

    return $build;
  }

}
