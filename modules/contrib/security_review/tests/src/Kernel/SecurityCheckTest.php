<?php

namespace Drupal\Tests\security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\security_review\SecurityCheckPluginManager;

/**
 * Contains tests for Checks.
 *
 * @group security_review
 */
class SecurityCheckTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'security_review'];

  /**
   * The security_review.plugin.manager.security_review.security_check service.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $pluginManager;

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\security_review\SecurityCheckInterface[]
   */
  protected array $checks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = \Drupal::service('plugin.manager.security_review.security_check');
    $this->checks = $this->pluginManager->getChecks();

    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
    user_install();
  }

  /**
   * Tests whether security check plugins are found.
   */
  public function testChecksExist(): void {
    $this->assertNotEmpty($this->checks);
  }

}
