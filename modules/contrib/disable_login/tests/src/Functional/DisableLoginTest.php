<?php

namespace Drupal\Tests\disable_login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test enabling and disabling of login page.
 *
 * @group disable_login
 */
class DisableLoginTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'disable_login',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('disable_login.settings')->set('disable_login', TRUE)->save();
    $this->config('disable_login.settings')->set('querystring', 'key')->save();
    $this->config('disable_login.settings')->set('secret', 'secret-value')->save();
  }

  /**
   * Case when disable login is enabled.
   *
   * User should get 403 when no key-querystring is provided.
   * User should get 403 when correct key wrong querystring is provided.
   * User should get 403 when wrong key correct querystring is provided.
   * User should get 403 when wrong key wrong querystring is provided.
   * User should get 200 when correct key-querystring is provided.
   */
  public function testAccessDeniedWithDisabledLoginEnabled() {
    $this->assertLoginDisabledResponse(TRUE);
  }

  /**
   * Case when disable login is disabled.
   *
   * User should get 200 in all scenarios.
   */
  public function testAccessOkWithDisableLoginDisabled() {
    $this->assertLoginDisabledResponse(FALSE);
  }

  /**
   * Executes a login page fetch with login page disabled/enabled.
   *
   * @param bool $enabled
   *   Disable login enabled / disabled.
   */
  public function assertLoginDisabledResponse($enabled) {
    $success_code = 200;
    $failure_code = 403;
    if (!$enabled) {
      $this->config('disable_login.settings')->set('disable_login', FALSE)->save();
      // When disabled no 403 is returned.
      $failure_code = 200;
    }
    // Ensure the user/login is not accessible without key.
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals($failure_code);

    // Ensure the user/login is not accessible with wrong key name.
    $this->drupalGet('user/login', ['query' => ['wrong' => 'secret-value']]);
    $this->assertSession()->statusCodeEquals($failure_code);

    // Ensure the user/login is not accessible with wrong key value.
    $this->drupalGet('user/login', ['query' => ['key' => 'wrong-secret-value']]);
    $this->assertSession()->statusCodeEquals($failure_code);

    // Ensure the user/login is accessible with correct key-value.
    $this->drupalGet('user/login', ['query' => ['key' => 'secret-value']]);
    $this->assertSession()->statusCodeEquals($success_code);
  }

}
