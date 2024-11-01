<?php

declare(strict_types=1);

namespace Drupal\Tests\flexible_permissions\Kernel;

use Drupal\Core\Session\AccessPolicyProcessorInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of the convertor access policy.
 *
 * @covers \Drupal\flexible_permissions\AccessPolicy
 * @group flexible_permissions
 */
class AccessPolicyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flexible_permissions', 'flexible_permissions_test'];

  /**
   * Tests that both FP and core access policies work alongside each other.
   */
  public function testAccessPolicy(): void {
    $processor = $this->container->get('access_policy_processor');
    assert($processor instanceof AccessPolicyProcessorInterface);

    $account = \Drupal::currentUser();
    $items = $processor->processAccessPolicies($account, 'flexible_permissions_test')->getItems();
    $this->assertEqualsCanonicalizing(['foo', 'foobar'], reset($items)->getPermissions());
  }

}
