<?php

namespace Drupal\Tests\security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\security_review\SecurityReview;

/**
 * Contains tests related to the SecurityReview class.
 *
 * @group security_review
 */
class SecurityReviewTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'security_review'];

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
    $this->installConfig(static::$modules);
    $this->installEntitySchema('user');
    $this->installConfig('security_review');
    $this->securityReview = \Drupal::service('security_review');
  }

  /**
   * Tests the 'logging' setting.
   */
  public function testConfigLogging(): void {
    $this->assertFalse($this->securityReview->isLogging(), 'Logging disabled.');
    $this->securityReview->setLogging(TRUE);
    $this->assertTrue($this->securityReview->isLogging(), 'Logging enabled by default.');
  }

  /**
   * Tests the 'untrusted_roles' setting.
   */
  public function testConfigUntrustedRoles(): void {
    $this->assertEquals(['anonymous', 'authenticated'], $this->securityReview->getUntrustedRoles(), 'untrusted_roles empty by default.');

    $roles = [0, 1, 2, 3, 4];
    $this->securityReview->setUntrustedRoles($roles);
    $this->assertEquals($roles, $this->securityReview->getUntrustedRoles(), 'untrusted_roles set to test array.');
  }

  /**
   * Tests the 'last_run' setting.
   */
  public function testConfigLastRun(): void {
    $this->assertEquals(0, $this->securityReview->getLastRun(), 'last_run is 0 by default.');
    $time = time();
    $this->securityReview->setLastRun($time);
    $this->assertEquals($time, $this->securityReview->getLastRun(), 'last_run set to now.');
  }

}
