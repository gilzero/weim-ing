<?php

namespace Drupal\Tests\raven\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\raven\LogLevel;

/**
 * Tests Raven module.
 *
 * @group raven
 */
class RavenTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['raven', 'raven_test'];

  /**
   * Tests Raven module configuration UI and hooks.
   */
  public function testRavenConfigAndHooks(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->assertNotEmpty($admin_user);
    $this->drupalLogin($admin_user);
    $config['raven[php][client_key]'] = 'https://user@sentry.test/123456';
    $config['raven[php][fatal_error_handler]'] = 1;
    // Enable all log levels except debug.
    foreach (LogLevel::cases() as $level) {
      if ($level === LogLevel::Debug) {
        continue;
      }
      $config["raven[php][log_levels][{$level->getPsrLogLevel()}]"] = TRUE;
    }
    $this->drupalGet('admin/config/development/logging');
    $this->submitForm($config, 'Save configuration');
    $this->assertSession()->responseHeaderEquals('X-Logged', 'Logged');
    $this->assertNull($this->getSession()->getResponseHeader('X-Not-Logged'));
    $this->assertSession()->responseHeaderEquals('X-Stacktrace-File', 'raven_test.module');

    // Test fatal error handling.
    $memory_limit = mt_rand(16000000, 17999999);
    $url = $admin_user->toUrl()->setOption('query', ['memory_limit' => $memory_limit]);
    // Output should be the memory limit and 0 pending events/requests.
    $this->assertEquals($memory_limit, $this->drupalGet($url));

    // Test that an uncaught exception is captured as unhandled.
    $this->drupalGet($url->setOption('query', ['throw' => '1']));
    $this->assertSame('0', file_get_contents('public://x-handled.txt'));
    unlink('public://x-handled.txt');

    // Test that a class-not-found error is captured as unhandled.
    $this->drupalGet($url->setOption('query', ['fatal' => '1']));
    $this->assertSame('0', file_get_contents('public://x-handled.txt'));
    unlink('public://x-handled.txt');

    // Test that @backtrace_string is removed.
    $this->drupalGet($url->setOption('query', ['error' => '1']));
    $this->assertSession()->responseHeaderNotContains('X-Uninterpolated', '@backtrace_string');

    // Test ignored channels.
    $config = ['raven[php][ignored_channels]' => "X-Logged\r\n"];
    $this->drupalGet('admin/config/development/logging');
    $this->submitForm($config, 'Save configuration');
    $this->assertNull($this->getSession()->getResponseHeader('X-Logged'));

    // Test ignored messages.
    $config = ['raven[php][ignored_channels]' => '', 'raven[php][ignored_messages]' => 'Logged'];
    $this->submitForm($config, 'Save configuration');
    $this->assertNull($this->getSession()->getResponseHeader('X-Logged'));

    // Test that logger can be serialized.
    serialize($this->container->get('logger.raven'));
  }

  /**
   * Tests performance tracing.
   */
  public function testRavenTracing(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'send performance traces to sentry']);
    $this->assertNotEmpty($admin_user);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/development/logging');
    $config['raven[php][client_key]'] = 'https://user@sentry.test/123456';
    $config['raven[php][trace]'] = TRUE;
    $config['raven[php][performance][request_tracing]'] = TRUE;
    $config['raven[php][performance][traces_sample_rate]'] = 1;
    $this->submitForm($config, 'Save configuration');
    $url = $admin_user->toUrl()->setOption('query', ['send_transaction' => '1']);
    $this->drupalGet($url);
    $this->assertGreaterThan(0, $this->getSession()->getResponseHeader('X-Sentry-Transaction-Frame-Vars'));
    $this->drupalGet('admin/config/development/logging');
    $config = ['raven[php][trace]' => FALSE];
    $this->submitForm($config, 'Save configuration');
    $this->drupalGet('admin/config/development', ['query' => ['send_transaction' => '1']]);
    $this->assertSession()->responseHeaderEquals('X-Sentry-Transaction-Frame-Vars', '0');
  }

}
