<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\group\Plugin\Validation\Constraint\GroupRoleScope;

/**
 * Tests the behavior of the group role form.
 *
 * @group group
 */
class GroupRoleFormTest extends GroupBrowserTestBase {

  /**
   * Group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * Group role storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected $groupRoleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpAccount();

    $this->groupRoleStorage = $this->entityTypeManager->getStorage('group_role');

    $this->groupType = $this->createGroupType([
      'id' => 'gt',
      'label' => 'community',
      'creator_membership' => FALSE,
    ]);
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'administer group',
    ] + parent::getGlobalPermissions();
  }

  /**
   * Test UI role creation via code.
   */
  public function testUiRoleCreation() {
    $this->drupalGet("/admin/group/types/manage/{$this->groupType->id()}/roles/add");
    $scope = 'outsider';
    $role_name = 'Outsider authenticated';
    $role_id = "{$this->groupType->id()}-outsider_authenticated";
    $submit_button = 'Save group role';
    $edit = [
      'Name' => $role_name,
      'id' => 'outsider_authenticated',
      'scope' => $scope,
      'Global role' => 'authenticated',
    ];
    $this->submitForm($edit, $submit_button);

    $this->assertSession()->pageTextContains("The group role {$role_name} has been added.");

    // Check that a newly created role has a correct id
    // (group_type_id-role_name)
    $group_role = $this->groupRoleStorage->load($role_id);
    $this->assertIsObject($group_role);

    // We want to be sure that we can edit role.
    $this->drupalGet("/admin/group/types/manage/{$this->groupType->id()}/roles/$role_id");
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], $submit_button);
    $this->assertSession()->pageTextContains("The group role {$role_name} has been updated.");

    // Try to create a role with the same scope.
    $this->drupalGet("/admin/group/types/manage/{$this->groupType->id()}/roles/add");
    $edit = [
      'Name' => 'new outsider authenticated',
      'id' => 'new_outsider_authenticated',
      'scope' => $scope,
      'Global role' => 'authenticated',
    ];
    $this->submitForm($edit, $submit_button);

    // We should see constraint message.
    $constraint = new GroupRoleScope();
    $this->assertSession()->pageTextContains(strip_tags(new FormattableMarkup($constraint->duplicateScopePairMessage, [
      '%group_type' => $this->groupType->label(),
      '@scope' => $scope,
      '%role' => 'Authenticated user',
    ])));

  }

  /**
   * Test role creation via code.
   */
  public function testCodeRoleCreation() {
    $role_id = 'my_role';
    $role_name = 'My role';
    $group_role = $this->groupRoleStorage->create([
      'id' => $role_id,
      'label' => $role_name,
      'scope' => 'outsider',
      'global_role' => 'anonymous',
      'group_type' => $this->groupType->id(),
    ]);
    $group_role->save();

    // Load role to be sure, we don't have problems with entity API.
    $group_role = $this->groupRoleStorage->load($role_id);
    $this->assertIsObject($group_role);

    $this->drupalGet("/admin/group/types/manage/{$this->groupType->id()}/roles/{$role_id}");
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([], 'Save group role');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The group role {$role_name} has been updated.");

  }

}
