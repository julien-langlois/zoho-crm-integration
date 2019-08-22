<?php

namespace Drupal\zoho_crm_integration\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ZohoCRMIntegrationScopesService.
 */
class ZohoCRMIntegrationScopesService implements ZohoCRMIntegrationScopesInterface {

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

}
