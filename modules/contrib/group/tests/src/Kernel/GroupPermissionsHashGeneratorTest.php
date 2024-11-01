<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the group permission hash generator service.
 *
 * @covers \Drupal\group\Access\GroupPermissionsHashGenerator
 * @covers \Drupal\group\Access\IndividualGroupPermissionCalculator
 * @covers \Drupal\group\Access\SynchronizedGroupPermissionCalculator
 * @group group
 */
class GroupPermissionsHashGeneratorTest extends GroupKernelTestBase {

  /**
   * The hash generator to run tests on.
   */
  protected GroupPermissionsHashGeneratorInterface $generator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->generator = $this->container->get('group_permission.hash_generator');
  }

  /**
   * Tests the permission hash for someone with no group permissions.
   */
  public function testNoPermissions(): void {
    $account_a = $this->createUser();
    $account_b = $this->createUser();

    // Run it twice to verify cache hits also return expected results.
    for ($i = 0; $i < 2; $i++) {
      $hash_a = $this->generator->generateHash($account_a);
      $hash_b = $this->generator->generateHash($account_b);
      $this->assertSame($hash_a, $hash_b);
    }
  }

  /**
   * Tests the permission hash for someone with individual permissions.
   *
   * @param array $role_config
   *   The configuration for the group role.
   *
   * @dataProvider individualProvider
   */
  public function testIndividual(array $role_config): void {
    $account_a = $this->createUser();
    $account_b = $this->createUser();
    $account_c = $this->createUser();

    $group_type = $this->createGroupType();
    $group_role = $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ] + $role_config);

    $group_a = $this->createGroup(['type' => $group_type->id()]);
    $group_b = $this->createGroup(['type' => $group_type->id()]);

    $group_a->addMember($account_a, ['group_roles' => [$group_role->id()]]);
    $group_b->addMember($account_b, ['group_roles' => [$group_role->id()]]);
    $group_b->addMember($account_c, ['group_roles' => [$group_role->id()]]);

    // Run it twice to verify cache hits also return expected results.
    for ($i = 0; $i < 2; $i++) {
      $hash_a = $this->generator->generateHash($account_a);
      $hash_b = $this->generator->generateHash($account_b);
      $hash_c = $this->generator->generateHash($account_c);
      $this->assertNotSame($hash_a, $hash_b);
      $this->assertSame($hash_b, $hash_c);
    }
  }

  /**
   * Data provider for testIndividual().
   *
   * @return array
   *   A list of testIndividual method arguments.
   */
  public function individualProvider() {
    $cases['regular'] = ['role_config' => ['permissions' => ['edit group']]];
    $cases['admin'] = ['role_config' => ['admin' => TRUE]];
    return $cases;
  }

  /**
   * Tests the permission hash for someone with synchronized permissions.
   *
   * @param array $role_config
   *   The configuration for the group role.
   *
   * @dataProvider synchronizedProvider
   */
  public function testSynchronized(array $role_config): void {
    $account_a = $this->createUser();
    $account_b = $this->createUser();
    $account_c = $this->createUser();

    $group_type = $this->createGroupType();
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ] + $role_config);

    $group_a = $this->createGroup(['type' => $group_type->id()]);
    $group_b = $this->createGroup(['type' => $group_type->id()]);

    $group_a->addMember($account_a);
    $group_b->addMember($account_b);
    $group_b->addMember($account_c);

    // Run it twice to verify cache hits also return expected results.
    for ($i = 0; $i < 2; $i++) {
      $hash_a = $this->generator->generateHash($account_a);
      $hash_b = $this->generator->generateHash($account_b);
      $hash_c = $this->generator->generateHash($account_c);
      $this->assertNotSame($hash_a, $hash_b);
      $this->assertSame($hash_b, $hash_c);
    }
  }

  /**
   * Data provider for testSynchronized().
   *
   * @return array
   *   A list of testSynchronized method arguments.
   */
  public function synchronizedProvider() {
    $cases['insider-regular'] = [
      'role_config' => [
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'permissions' => ['edit group'],
      ],
    ];
    $cases['insider-admin'] = [
      'role_config' => [
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'admin' => TRUE,
      ],
    ];
    $cases['outsider-regular'] = [
      'role_config' => [
        'scope' => PermissionScopeInterface::OUTSIDER_ID,
        'permissions' => ['edit group'],
      ],
    ];
    $cases['outsider-admin'] = [
      'role_config' => [
        'scope' => PermissionScopeInterface::OUTSIDER_ID,
        'admin' => TRUE,
      ],
    ];
    return $cases;
  }

}
