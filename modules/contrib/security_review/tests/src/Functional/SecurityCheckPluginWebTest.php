<?php

namespace Drupal\Tests\security_review\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Contains tests for Check that don't suffice with KernelTestBase.
 *
 * @group security_review
 */
class SecurityCheckPluginWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'security_review',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

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

    // Login.
    $this->user = $this->drupalCreateUser(
      [
        'run security checks',
        'access security review list',
        'access administration pages',
        'administer site configuration',
      ]
    );
    $this->drupalLogin($this->user);

    // Get checks.
    $this->checks = \Drupal::service('plugin.manager.security_review.security_check')->getChecks();
  }

  /**
   * Tests Check::skip().
   *
   * Checks whether skip() marks the check as skipped, and checks the
   * skippedBy() value.
   */
  public function testSkipCheck(): void {
    $security_review_service = \Drupal::service('security_review');
    foreach ($this->checks as $check) {
      $name = $check->getPluginId();
      $security_review_service->skip($name);

      $skipped_info = $security_review_service->isCheckSkipped($name);
      $this->assertTrue(is_array($skipped_info));
      $this->assertTrue($skipped_info['skipped']);
      $this->assertEquals($this->user->id(), $skipped_info['skipped_by']);
      // Not testing time as it would be a random failure.
    }
  }

}
