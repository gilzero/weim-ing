<?php

namespace Drupal\Tests\raven\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Raven and Security Kit modules.
 *
 * @group raven
 * @requires module seckit
 */
class SecKitTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['raven', 'seckit'];

  /**
   * Tests Sentry browser client configuration UI.
   */
  public function testRavenJavascriptConfig(): void {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'send javascript errors to sentry',
      'administer seckit',
    ]);
    $this->assertNotEmpty($admin_user);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/development/logging');
    $this->submitForm([
      'raven[js][javascript_error_handler]' => TRUE,
      'raven[js][public_dsn]' => 'https://a@domain.test/1',
      'raven[js][seckit_set_report_uri]' => TRUE,
    ], 'Save configuration');

    $this->drupalGet('admin/config/system/seckit');
    $this->submitForm([
      'seckit_xss[csp][checkbox]' => '1',
      'seckit_xss[csp][report-only]' => '1',
    ], 'Save configuration');
    $this->assertSession()->responseHeaderEquals('Content-Security-Policy-Report-Only', "report-uri https://domain.test/api/1/security/?sentry_key=a&sentry_environment=prod");

    $this->drupalGet('admin/config/system/seckit');
    $this->submitForm([
      'seckit_xss[csp][default-src]' => "'self'",
    ], 'Save configuration');
    $this->assertSession()->responseHeaderEquals('Content-Security-Policy-Report-Only', "default-src 'self'; connect-src 'self' https://domain.test/api/1/envelope/; report-uri https://domain.test/api/1/security/?sentry_key=a&sentry_environment=prod");
  }

}
