<?php

namespace Drupal\zoho_crm_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\zoho_crm_integration\Service\ZohoCRMAuthService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use zcrmsdk\oauth\exception\ZohoOAuthException;

/**
 * ZohoCRMIntegrationRevoke Controller class.
 */
class ZohoCRMIntegrationRevoke extends ControllerBase {

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
   * Revoke the refresh token.
   */
  public function revoke() {
    try {
      $this->authService->revokeRefreshToken();
      $this->messenger->addMessage($this->t('Your access token was revoked.'), 'status');
    }
    catch (GuzzleException $e) {
      $this->messenger->addMessage($this->t('We have problems trying revoke your token.'), 'error');
      // TODO: Log error message.
    }
    catch (ZohoOAuthException $e) {
      $this->messenger->addMessage($this->t('We have problems trying revoke your token.'), 'error');
      // TODO: Log error message.
    }

    return $this->redirect('zoho_crm_integration.settings');
  }

}
