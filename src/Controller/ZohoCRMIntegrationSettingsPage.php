<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zoho_crm_integration\Service\ZohoCRMAuthService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module settings page controller.
 */
class ZohoCRMIntegrationSettingsPage extends ControllerBase {

  /**
   * The Zoho CRM Auth service.
   *
   * @var Drupal\zoho_crm_integration\Service\ZohoCRMAuthService
   */
  protected $authService;

  /**
   * Controller Constructor.
   *
   * @param \Drupal\zoho_crm_integration\Service\ZohoCRMAuthService $auth_service
   *   The module handler service.
   */
  public function __construct(ZohoCRMAuthService $auth_service) {
    $this->authService = $auth_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zoho_crm_integration.auth')
    );
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    // Retrieve form.
    $form = $this->formBuilder()->getForm('Drupal\zoho_crm_integration\Form\ZohoCRMIntegrationForm');

    // Retrieve authentication service.
    $status = $this->authService->checkConnection();
    $auth_url = $this->authService->getAuthorizationUrl();

    // Check for redirect param code.
    if (!$status && isset($_GET['code'])) {
      $tokens = $this->authService->generateAccessToken($_GET['code']);
      if (is_object($tokens)) {
        \Drupal::service('messenger')->addMessage(t('You get Authorization on your Zoho CRM.'), 'status');
      }
    }

    $build = [
      '#theme' => 'zoho_crm_integration__settings_page',
      '#form' => $form,
      '#status' => $status,
      '#auth_url' => $auth_url,
    ];

    return $build;
  }

}
