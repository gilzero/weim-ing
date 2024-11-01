<?php

namespace Drupal\Tests\security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Drupal\security_review\SecurityReviewHelperTrait;

/**
 * Contains test for Checklist.
 *
 * @group security_review
 */
class SecurityCheckPluginManagerTest extends KernelTestBase {

  use SecurityReviewHelperTrait;

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
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReview;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->securityReview = \Drupal::service('security_review');
    $this->pluginManager = \Drupal::service('plugin.manager.security_review.security_check');
  }

  /**
   * Tests the ability to retrieve check plugins.
   */
  public function testGetChecks(): void {
    $checks = $this->pluginManager->getChecks();
    $checkIDs = [];
    foreach ($checks as $check) {
      $checkIDs[] = $check->getPluginId();
    }
    foreach ($checks as $check) {
      $this->assertTrue(in_array($check->getPluginId(), $checkIDs));
    }
  }

  /**
   * Tests PluginManager Check search functions.
   */
  public function testCheckSearch(): void {
    foreach ($this->pluginManager->getChecks() as $check) {
      // Test getCheck().
      $found = $this->pluginManager->getCheck($this->getMachineName($check->getNamespace()), $this->getMachineName($check->getTitle()));
      $this->assertEquals($check->getPluginId(), $found->getPluginId());

      // Test getCheckById().
      $found = $this->pluginManager->getCheckById($check->getPluginId());
      $this->assertEquals($check->getPluginId(), $found->getPluginId());
    }
  }

}
