<?php

namespace Drupal\zoho_crm_integration\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zoho_crm_integration\Service\ZohoCRMIntegrationScopesService;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * ZohoCRMIntegrationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\zoho_crm_integration\Service\ZohoCRMIntegrationScopesService $scopes_service
   */
  public function __construct(ConfigFactoryInterface $config_factory, ZohoCRMIntegrationScopesService $scopes_service) {
    parent::__construct($config_factory);

    $this->scopes_service = $scopes_service;
    $this->all_scopes = $scopes_service->getAllScopes();
    $this->flattened_scopes = $scopes_service->getFlattenedScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('zoho_crm_integration.scopes')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Capitalize value given as parameter.
   *
   * @param $value
   *  String to capitalize.
   *
   * @return string
   *  Capitalized string.
   */
  protected function capitalize($value) {
    return Unicode::ucfirst($value);
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

    $form['container'] = [
      '#type' => 'container',
      '#id' => 'zoho-settings-container',
      '#prefix' => '<h2>' . $this->t('Scopes') . '</h2>',
    ];

    // Retrieve scopes service.
    foreach ($this->all_scopes as $group => $scopes) {
      // Scope group title.
      $form['container'][$group] = [
        '#type' => 'container',
        '#markup' => '<h3>' . $this->capitalize($group) . '</h3>',
      ];

      // Iterate scopes and create one checkbox form item for each one of them.
      // We're using #checkbox as opposed to #checkboxes because it allows us
      // to then save scope values as boolean values, i.e. "users.all = 1" while
      // maintaining a user-friendly label.
      foreach ($scopes as $scope) {
        // Actual value saved in config.
        $config_value = "{$group}_{$scope}";

        $form['container'][$group][$config_value] = [
          '#type' => 'checkbox',
          '#title' => $scope,
          '#default_value' => $config->get($config_value),
          '#return_value' => 1,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve config factory editable.
    $config_settings = $this->configFactory->getEditable(static::SETTINGS);

    // Form settings set in text elements.
    $text_configs = ['client_id', 'client_secret', 'current_user_email'];

    // Scopes set in checkbox elements.
    $scope_configs = $this->scopes_service->getFlattenedScopes();

    // Merge  both text and checkbox configs.
    $all_configs = array_merge($text_configs, $scope_configs);

    // Iterate, set configs and then save.
    foreach ($all_configs as $single_config) {
      $config_settings->set($single_config, $form_state->getValue($single_config));
    }
    $config_settings->save();

    parent::submitForm($form, $form_state);
  }

}
