<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Tests the group creator wizard.
 *
 * @group group
 */
class GroupCreatorWizardTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpAccount();
  }

  /**
   * Tests that a group creator gets a membership using the wizard.
   */
  public function testCreatorMembershipWizard() {
    $group_type = $this->createGroupTypeAndRole(TRUE, TRUE);

    $this->drupalGet("/group/add/{$group_type->id()}");
    $this->assertSession()->statusCodeEquals(200);

    $group_role = $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);
    $group_type->set('creator_roles', [$group_role->id()]);
    $group_type->save();

    $submit_button = 'Create ' . $group_type->label() . ' and complete your membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    $submit_button = 'Save group and membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Back');

    $this->submitForm([], $submit_button);
    $this->assertSession()->statusCodeEquals(200);

    // Get the group.
    $all_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $this->assertCount(1, $all_groups);
    $group = reset($all_groups);

    // Check there is just one membership.
    $membership_ids = $this->loadGroupMembershipIds($group, $this->groupCreator);
    $this->assertCount(1, $membership_ids, 'Wizard set just one membership.');

    // Check that the roles assigned to the created member are the same as what
    // we configured in the group defaults.
    $ids = array_column($group->getMember($this->groupCreator)->getGroupRelationship()->get('group_roles')->getValue(), 'target_id');
    $this->assertEquals($group_type->getCreatorRoleIds(), $ids, 'Wizard set the correct creator roles.');
  }

  /**
   * Tests that a group creator gets a membership without using the wizard.
   */
  public function testCreatorMembershipNoWizard() {
    $group_type = $this->createGroupTypeAndRole(TRUE, FALSE);

    $this->drupalGet("/group/add/{$group_type->id()}");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label() . ' and become a member';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    // Get the group.
    $all_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $this->assertCount(1, $all_groups);
    $group = reset($all_groups);

    // @todo We do not want this behavior in Group 4.0.0, we only want the
    //   wizard to create a membership and assign roles.
    // Check there is just one membership.
    $membership_ids = $this->loadGroupMembershipIds($group, $this->groupCreator);
    $this->assertCount(1, $membership_ids, 'Wizard set just one membership.');

    // Check that the roles assigned to the created member are the same as what
    // we configured in the group defaults.
    $ids = array_column($group->getMember($this->groupCreator)->getGroupRelationship()->get('group_roles')->getValue(), 'target_id');
    $this->assertEquals($group_type->getCreatorRoleIds(), $ids, 'Group::postCreate() correctly set the creator roles.');
  }

  /**
   * Tests that a group form is not turned into a wizard.
   */
  public function testNoWizard() {
    $group_type = $this->createGroupTypeAndRole(FALSE, FALSE);

    $this->drupalGet("/group/add/{$group_type->id()}");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label();
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    // Get the group.
    $all_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $this->assertCount(1, $all_groups);
    $group = reset($all_groups);

    // Check there is no membership.
    $membership_ids = $this->loadGroupMembershipIds($group, $this->groupCreator);
    $this->assertCount(0, $membership_ids, 'Group creation did not result in a membership.');
    $this->assertFalse($group->getMember($this->groupCreator), 'No membership found for group creator.');
  }

  /**
   * Creates group type and role with creation rights.
   *
   * @param bool $creator_membership
   *   The group creator automatically receives a membership.
   * @param bool $creator_wizard
   *   The group creator must immediately complete their membership.
   *
   * @return \Drupal\group\Entity\GroupType
   *   Group type.
   */
  protected function createGroupTypeAndRole($creator_membership, $creator_wizard) {
    $group_type = $this->createGroupType([
      'creator_membership' => $creator_membership,
      'creator_wizard' => $creator_wizard,
    ]);

    $role = $this->drupalCreateRole(["create {$group_type->id()} group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    return $group_type;
  }

  /**
   * Loads all membership IDs for a user in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group used to get the membership IDs.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account used to get the membership IDs.
   *
   * @return int[]
   *   The memberships IDs.
   */
  protected function loadGroupMembershipIds(GroupInterface $group, AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('group_relationship');

    return $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->execute();
  }

}
