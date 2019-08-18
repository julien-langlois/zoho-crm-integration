<?php

namespace Drupal\Tests\zoho_crm_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Assure that permissions are properly set on Zoho settings page.
 */
class ZohoCRMIntegrationPermissionsTest extends BrowserTestBase {

  /**
   * The mocked user with "Administer Zoho Settings" permission.
   */
  protected $accountWithPermissions;

  /**
   * The mocked user without "Administer Zoho Settings" permission.
   */
  protected $accountWithNoPermissions;

  /**
   * Zoho settings form path.
   */
  protected $settingsPath;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'zoho_crm_integration',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up accounts accordingly.
    $this->accountWithPermissions = $this->drupalCreateUser(['administer zoho settings']);
    $this->accountWithNoPermissions = $this->drupalCreateUser();

    // Get settings form path.
    $this->settingsPath = Url::fromRoute('zoho_crm_integration.settings');
  }

  /**
   * Login as user with "Administer Zoho Settings" permission and make sure
   * access is granted.
   */
  public function testAccountWithPermissions() {
    $this->drupalLogin($this->accountWithPermissions);
    $this->drupalGet($this->settingsPath);

    // Make sure 200 (OK) response is returned.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Login as authenticated user without "Administer Zoho Settings" permission
   * and make sure access is not granted.
   */
  public function testAccountWithNoPermissions() {
    $this->drupalLogin($this->accountWithNoPermissions);
    $this->drupalGet($this->settingsPath);

    // Make sure 403 (Access Denied) response is returned.
    $this->assertSession()->statusCodeEquals(403);
  }
}
