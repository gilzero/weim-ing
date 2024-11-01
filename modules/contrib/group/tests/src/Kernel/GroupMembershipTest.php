<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionCheckerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the GroupMembership shared bundle class.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupMembership
 * @group group
 */
class GroupMembershipTest extends GroupKernelTestBase {

  /**
   * The account to use in testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected GroupTypeInterface $groupType;

  /**
   * The insider group role to use in testing.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected GroupRoleInterface $groupRoleInsider;

  /**
   * The individual group role to use in testing.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected GroupRoleInterface $groupRoleIndividual;

  /**
   * The group to use in testing.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group;

  /**
   * The group membership to run tests on.
   *
   * @var \Drupal\group\Entity\GroupMembershipInterface
   */
  protected GroupMembershipInterface $groupMembership;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createUser();
    $this->groupType = $this->createGroupType(['creator_membership' => FALSE]);
    $this->groupRoleInsider = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ]);
    $this->groupRoleIndividual = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'permissions' => ['view group'],
    ]);

    // Reload the roles so that we can do proper comparison of loaded roles.
    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->groupRoleInsider = $storage->load($this->groupRoleInsider->id());
    $this->groupRoleIndividual = $storage->load($this->groupRoleIndividual->id());

    $this->group = $this->createGroup(['type' => $this->groupType->id()]);
    $this->group->addMember($this->account, ['group_roles' => [$this->groupRoleIndividual->id()]]);

    // Manually load the membership here using the storage so that we don't
    // end up testing ::loadSingle() via a detour.
    $memberships = $this->entityTypeManager
      ->getStorage('group_relationship')
      ->loadByProperties([
        'gid' => $this->group->id(),
        'entity_id' => $this->account->id(),
        'plugin_id' => 'group_membership',
      ]);

    $this->groupMembership = reset($memberships);
  }

  /**
   * Tests the retrieval of a membership's group roles.
   *
   * @covers ::getRoles
   */
  public function testGetRoles() {
    $expected[$this->groupRoleIndividual->id()] = $this->groupRoleIndividual;
    $this->assertEquals($expected, $this->groupMembership->getRoles(FALSE));

    $expected[$this->groupRoleInsider->id()] = $this->groupRoleInsider;
    $this->assertEquals($expected, $this->groupMembership->getRoles());
  }

  /**
   * Tests the addition of a group role to a membership.
   *
   * @covers ::addRole
   * @depends testGetRoles
   */
  public function testAddRole() {
    $group_role = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);

    $expected = [
      $this->groupRoleIndividual->id(),
      $this->groupRoleInsider->id(),
      $group_role->id(),
    ];
    $this->groupMembership->addRole($group_role->id());
    $this->assertEqualsCanonicalizing($expected, array_keys($this->groupMembership->getRoles()));
  }

  /**
   * Tests the removal of a group role from a membership.
   *
   * @covers ::removeRole
   * @depends testGetRoles
   */
  public function testRemoveRole() {
    $this->groupMembership->removeRole($this->groupRoleIndividual->id());
    $this->assertEquals([$this->groupRoleInsider->id()], array_keys($this->groupMembership->getRoles()));
  }

  /**
   * Tests the permission check on a membership.
   *
   * @covers ::hasPermission
   */
  public function testHasPermission() {
    // This should always be a wrapper around the permission checker, so check.
    $permission_checker = \Drupal::service('group_permission.checker');
    assert($permission_checker instanceof GroupPermissionCheckerInterface);

    $expected = $permission_checker->hasPermissionInGroup('view group', $this->account, $this->group);
    $this->assertSame($expected, $this->groupMembership->hasPermission('view group'));

    $expected = $permission_checker->hasPermissionInGroup('edit group', $this->account, $this->group);
    $this->assertSame($expected, $this->groupMembership->hasPermission('edit group'));
  }

  /**
   * Tests the loading of a single membership.
   *
   * @covers ::loadSingle
   */
  public function testLoadSingle() {
    $membership = GroupMembership::loadSingle($this->group, $this->account);
    $this->assertSame($this->groupMembership->id(), $membership->id());

    // Check non-matching retrievals.
    $this->assertFalse(GroupMembership::loadSingle($this->group, $this->createUser()));
    $this->assertFalse(GroupMembership::loadSingle($this->createGroup(['type' => $this->groupType->id()]), $this->account));
    $this->assertFalse(GroupMembership::loadSingle($this->createGroup(['type' => $this->groupType->id()]), $this->createUser()));
  }

  /**
   * Tests the loading of all memberships of a group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByGroup() {
    $expected = [$this->account->id()];

    // Add a new member to the group twice to check that caches don't break.
    for ($i = 0; $i < 2; $i++) {
      $account = $this->createUser();
      $expected[] = $account->id();

      $this->group->addMember($account);

      $account_ids = [];
      foreach (GroupMembership::loadByGroup($this->group) as $membership) {
        assert($membership instanceof GroupMembershipInterface);
        $account_ids[] = $membership->getEntityId();
      }
      $this->assertSame($expected, $account_ids);
    }
  }

  /**
   * Tests the loading of all memberships of a user.
   *
   * @covers ::loadByUser
   */
  public function testLoadByUser() {
    $expected = [$this->group->id()];

    // Add a membership for the user twice to check that caches don't break.
    for ($i = 0; $i < 2; $i++) {
      $group = $this->createGroup(['type' => $this->groupType->id()]);
      $expected[] = $group->id();

      $group->addMember($this->account);

      $group_ids = [];
      foreach (GroupMembership::loadByUser($this->account) as $membership) {
        assert($membership instanceof GroupMembershipInterface);
        $group_ids[] = $membership->getGroupId();
      }
      $this->assertSame($expected, $group_ids);
    }
  }

}
