<?php

namespace Drupal\zoho_crm_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ZohoCRMIntegrationScopesService.
 */
class ZohoCRMIntegrationScopesService implements ZohoCRMIntegrationScopesInterface {

  /**
   * ZohoCRMIntegrationScopesService constructor.
   *
   * @param \Drupal\zoho_crm_integration\Service\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->settings = $config_factory->get('zoho_crm_integration.settings');
  }

  /**
   * Retrieve scopes from .yml.
   *
   * @return array
   *  Scopes.
   */
  protected static function getScopes() {
    $full_path = drupal_get_path('module', 'zoho_crm_integration') . '/' . self::SCOPES_FILE_PATH;

    return Yaml::parse(file_get_contents($full_path));
  }

  /**
   * Get all available scopes.
   *
   * @return array
   *  Array version of scopes .yml file.
   */
  public static function getAllScopes() {
    return self::getScopes();
  }

  /**
   * Get scopes from a particular group.
   *
   * @param $group
   *  Desired group to retrieve scopes from.
   *
   * @return bool|mixed
   *  Return scopes for a given group in array format or false if group doesn't
   *  exist.
   */
  public static function getGroupScopes($group) {
    $scopes = self::getScopes();

    if (isset($scopes[$group])) {
      return $scopes[$group];
    }
    else {
      return false;
    }
  }

  /**
   * Return scope array in a more config-friendly manner, i.e. "users.all".
   *
   * @return array
   *  Flattened scopes.
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
   *  String with scope parameters to put in request URL.
   */
  public function getScopesParameters() {
    // "aaaserver.profile.read" should be added as default.
    $parameters = ['aaaserver.profile.read'];
    $scopes = self::getFlattenedScopes();

    foreach ($scopes as $scope) {
      if ($config = $this->settings->get($scope)) {
        // Group/scopes should be separated using "." as opposed to "_".
        $parameters[] = str_replace('_', '.', $scope);
      }
    }

    return urldecode(implode(',', $parameters));
  }

}
