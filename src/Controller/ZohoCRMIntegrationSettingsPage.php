<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zoho_crm_integration\Service\ZohoCRMAuthService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;

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
   * The Drupal messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Controller Constructor.
   *
   * @param \Drupal\zoho_crm_integration\Service\ZohoCRMAuthService $auth_service
   *   The module handler service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(ZohoCRMAuthService $auth_service, Messenger $messenger) {
    $this->authService = $auth_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zoho_crm_integration.auth'),
      $container->get('messenger')
    );
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    // Retrieve form.
    $form = $this->formBuilder()->getForm('Drupal\zoho_crm_integration\Form\ZohoCRMIntegrationForm');

    // Auth Services parameters.
    $status = $this->authService->checkConnection();
    $auth_url = $this->authService->getAuthorizationUrl();
    $revoke_url = $this->authService->getRevokeUrl();

    // Check for redirect param code.
    if (!$status && isset($_GET['code'])) {
      $tokens = $this->authService->generateAccessToken($_GET['code']);
      if (is_object($tokens)) {
        $this->messenger->addMessage($this->t('You get Authorization on your Zoho CRM.'), 'status');
      }
    }

    if ($status) {
      $this->messenger->addMessage($this->t('You are connected. Note that you will have access only on the scopes you selected on the form.'), 'status');
    }
    else {
      $this->messenger->addMessage($this->t('You are not connected yet. Add your configurations below and Get Authorization to start.'), 'warning');
    }

    $build = [
      '#theme' => 'zoho_crm_integration__settings_page',
      '#form' => $form,
      '#auth_url' => $auth_url,
      '#revoke_url' => $revoke_url,
      '#status' => $status,
      '#attached' => [
        'library' => [
          'zoho_crm_integration/zoho-settings' => 'zoho_crm_integration/zoho-settings',
        ],
      ],
    ];

    return $build;
  }

}
