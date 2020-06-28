<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zoho_crm_integration\Service\ZohoCRMAuthService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\UrlGeneratorInterface;

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
   * The Drupal URL Generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Controller Constructor.
   *
   * @param \Drupal\zoho_crm_integration\Service\ZohoCRMAuthService $auth_service
   *   The module handler service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The UrlGeneratorInterface service.
   */
  public function __construct(ZohoCRMAuthService $auth_service, Messenger $messenger, UrlGeneratorInterface $url_generator) {
    $this->authService = $auth_service;
    $this->messenger = $messenger;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zoho_crm_integration.auth'),
      $container->get('messenger'),
      $container->get('url_generator')
    );
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
    if (!$this->authService->checkSdkClass()) {
      $this->messenger->addMessage($this->t('You have not installed the Zoho SDK.'), 'error');

      return [
        '#markup' => $this->t('To use this page you need to get the Zoho SDK using composer.'),
      ];
    }

    // Retrieve form.
    $form = $this->formBuilder()->getForm('Drupal\zoho_crm_integration\Form\ZohoCRMIntegrationForm');

    // Auth Services parameters.
    $status = $this->authService->checkConnection();
    $has_client_id = $this->authService->hasClientId();
    $auth_url = $this->authService->getAuthorizationUrl();
    $revoke_url = $this->urlGenerator->generateFromRoute('zoho_crm_integration.revoke');
    $redirect_link = $this->authService->redirectUrl;

    // Check for redirect param code.
    if (!$status && isset($_GET['code'])) {
      $access = $this->authService->generateAccessToken($_GET['code']);
      if ($access) {
        $this->messenger->addMessage($this->t('You get Authorization on your Zoho CRM.'), 'status');
      }
    }

    if ($status) {
      $this->messenger->addMessage($this->t('You are connected. Note that you will have access only on the scopes you selected on the form.'), 'status');
    }
    else {
      $this->messenger->addMessage($this->t('You are not connected yet. Add your Zoho Client configurations below to be able to get you Authorization.'), 'warning');
    }

    $build = [
      '#theme' => 'zoho_crm_integration__settings_page',
      '#form' => $form,
      '#auth_url' => $auth_url,
      '#revoke_url' => $revoke_url,
      '#status' => $status,
      '#has_client_id' => $has_client_id,
      '#redirect_link' => $redirect_link,
      '#attached' => [
        'library' => [
          'zoho_crm_integration/zoho-settings' => 'zoho_crm_integration/zoho-settings',
        ],
      ],
    ];

    return $build;
  }

}
