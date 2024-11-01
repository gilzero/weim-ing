<?php

namespace Drupal\Tests\raven\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Raven module.
 *
 * @group raven
 */
class RavenTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['raven'];

  /**
   * Tests Sentry browser client configuration UI.
   */
  public function testRavenJavascriptConfig(): void {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'send javascript errors to sentry',
    ]);
    $this->assertNotEmpty($admin_user);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/development/logging');
    $this->submitForm(['raven[js][javascript_error_handler]' => TRUE], 'Save configuration');
  }

}
