<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ZohoCRMIntegrationScopesService.
 */
class ZohoCRMIntegrationScopesService implements ZohoCRMIntegrationScopesInterface {

  /**
   * Drupal config factory services.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Setting form configurations.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $settings;

  /**
   * ZohoCRMIntegrationScopesService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal Config Factory services.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->settings = $config_factory->get('zoho_crm_integration.settings');
  }

  /**
   * Retrieve scopes from .yml.
   *
   * @return array
   *   Scopes.
   */
  protected static function getScopes() {
    $full_path = drupal_get_path('module', 'zoho_crm_integration') . '/' . self::SCOPES_FILE_PATH;

    return Yaml::parse(file_get_contents($full_path));
  }

  /**
   * Get all available scopes.
   *
   * @return array
   *   Array version of scopes .yml file.
   */
  public static function getAllScopes() {
    return self::getScopes();
  }

  /**
   * Get scopes from a particular group.
   *
   * @param string $group
   *   Desired group to retrieve scopes from.
   *
   * @return bool|mixed
   *   Return scopes for a given group in array format or false if group doesn't
   *   exist.
   */
  public static function getGroupScopes($group) {
    $scopes = self::getScopes();

    if (isset($scopes[$group])) {
      return $scopes[$group];
    }

    return FALSE;
  }

  /**
   * Return scope array in a more config-friendly manner, i.e. "users.all".
   *
   * @return array
   *   Flattened scopes.
   */
  public static function getFlattenedScopes() {
    $all_scopes = self::getAllScopes();
    $flattened_scopes = [];

    foreach ($all_scopes as $group => $scopes) {
      foreach ($scopes as $scope) {
        $flattened_scopes[] = "{$group}_{$scope}";
      }
    }

    return $flattened_scopes;
  }

  /**
   * Build URL with scope paramenters.
   *
   * @return string
   *   String with scope parameters to put in request URL.
   */
  public function getScopesParameters() {
    // "aaaserver.profile.read" should be added as default.
    $parameters = ['aaaserver.profile.read'];
    $scopes = self::getFlattenedScopes();

    foreach ($scopes as $scope) {
      if ($this->settings->get($scope)) {
        // Group/scopes should be separated using "." as opposed to "_".
        $scope_name = str_replace('_', '.', $scope);

        // All modules and settings scopes end with .ALL to allow READ and WRITE.
        $is_module = (strpos($scope_name, 'modules.') === 0 && $scope_name !== 'modules.all');
        $is_settings = (strpos($scope_name, 'settings.') === 0 && $scope_name !== 'settings.all');
        $suffix = ($is_module || $is_settings) ? '.ALL' : '';

        // All scopes need to start with ZohoCRM.
        $parameters[] = "ZohoCRM.{$scope_name}{$suffix}";
      }
    }

    return urldecode(implode(',', $parameters));
  }

}
