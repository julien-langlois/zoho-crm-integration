<?php

namespace Drupal\zoho_crm_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZohoCRMIntegrationForm.
 *
 * @package Drupal\zoho_crm_integration\Form
 */
class ZohoCRMIntegrationForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'zoho_crm_integration.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zoho_crm_integration_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    $form['current_user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Current User Email'),
      '#default_value' => $config->get('current_user_email'),
      '#required' => TRUE,
    ];

    $form['zoho_domain'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose your Zoho Domain'),
      '#default_value' => $config->get('zoho_domain'),
      '#options' => [
        'default' => '.COM (Default)',
        'https://accounts.zoho.eu' => '.EU',
        'https://accounts.zoho.com.cn' => '.CN',
        'https://accounts.zoho.in' => '.IN',
      ],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('current_user_email', $form_state->getValue('current_user_email'))
      ->set('zoho_domain', $form_state->getValue('zoho_domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
