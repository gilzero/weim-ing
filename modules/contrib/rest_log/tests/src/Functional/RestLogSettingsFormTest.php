<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the REST Log settings form.
 *
 * @group rest_log
 */
class RestLogSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest_log'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    // Create a user that have administer site configuration.
    $user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the rest log settings form access.
   */
  public function testAccess() {
    // Check access when logged in as user with required permission.
    $this->drupalGet('/admin/config/development/logging/rest_log');
    $this->assertSession()->statusCodeEquals(200);

    // Create a user without administer site configuration permission.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/development/logging/rest_log');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that the rest log settings form appears and can be submitted.
   */
  public function testSubmission() {
    $this->drupalGet('/admin/config/development/logging/rest_log');
    $this->assertSession()->fieldValueEquals('maximum_lifetime', 2592000);
    $this->assertSession()->fieldValueEquals('include_same_host', TRUE);

    $edit = [
      'maximum_lifetime' => 5184000,
      'include_same_host' => FALSE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Verify if module settings have been updated.
    $module_config = $this->configFactory->get('rest_log.settings');
    $this->assertEquals(5184000, $module_config->get('maximum_lifetime'),
      'Maximum lifetime has been updated.');
    $this->assertEquals(FALSE, $module_config->get('include_same_host'),
      'Include same host setting has been updated.');
  }

}
