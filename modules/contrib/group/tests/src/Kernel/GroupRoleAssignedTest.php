<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests for the GroupRoleAssigned constraint.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Plugin\Validation\Constraint\GroupRoleAssignedValidator
 */
class GroupRoleAssignedTest extends GroupKernelTestBase {

  /**
   * Tests individual roles.
   *
   * @covers ::validate
   */
  public function testIndividualRole(): void {
    $group_type_id = $this->createGroupType()->id();
    $individual_role = [
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'group_type' => $group_type_id,
    ];

    // Verify the absence of violations in a newly created role.
    $group_role = $this->createGroupRole($individual_role);
    $violations = $group_role->getTypedData()->validate();
    $this->assertCount(0, $violations, 'A freshly created role has no violations.');

    // Assign the role to a member of a group.
    $this->createGroup(['type' => $group_type_id])->addMember($this->createUser(), ['group_roles' => [$group_role->id()]]);

    // Verify that are still no violations on the group role.
    $violations = $group_role->getTypedData()->validate();
    $this->assertCount(0, $violations, 'Assigning an individual role still leads to no violations.');

    // Create another insider role and verify that it has no violations.
    $violations = $this->createGroupRole($individual_role)->getTypedData()->validate();
    $this->assertCount(0, $violations, 'Another freshly created role has no violations.');
  }

  /**
   * Tests synchronized roles.
   *
   * @param string $scope
   *   The synchronized scope to create the group role in.
   *
   * @covers ::validate
   * @dataProvider synchronizedRoleProvider
   */
  public function testSynchronizedRole(string $scope): void {
    $group_type_id_a = $this->createGroupType()->id();
    $group_type_id_b = $this->createGroupType()->id();
    $group_type_id_c = $this->createGroupType()->id();
    $individual_role = ['scope' => PermissionScopeInterface::INDIVIDUAL_ID];
    $synchronized_role = ['scope' => $scope, 'global_role' => RoleInterface::AUTHENTICATED_ID];

    // Verify the absence of violations in a newly created role.
    $group_role = $this->createGroupRole($synchronized_role + ['group_type' => $group_type_id_a]);
    $violations = $group_role->getTypedData()->validate();
    $this->assertCount(0, $violations, 'A freshly created role has no violations.');

    // Assign an individual role to a member and then change its scope.
    $group_role = $this->createGroupRole($individual_role + ['group_type' => $group_type_id_b]);
    $this->createGroup(['type' => $group_type_id_b])->addMember($this->createUser(), ['group_roles' => [$group_role->id()]]);
    $group_role->set('scope', $scope);
    $group_role->set('global_role', RoleInterface::AUTHENTICATED_ID);

    // Verify that there is now a violation on the group role.
    $violations = $group_role->getTypedData()->validate();
    $this->assertCount(1, $violations, 'Assigning the role to a member triggers a violation.');
    $message = new TranslatableMarkup(
      'Cannot save this group role in the %scope scope as it has already been assigned to individual members.',
      ['%scope' => $group_role->getScope()]
    );
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());

    // Create another role and verify that it has no violations.
    $violations = $this->createGroupRole($synchronized_role + ['group_type' => $group_type_id_c])->getTypedData()->validate();
    $this->assertCount(0, $violations, 'Another freshly created role has no violations.');
  }

  /**
   * Data provider for testSynchronizedRole().
   *
   * @return array
   *   A list of testSynchronizedRole method arguments.
   */
  public function synchronizedRoleProvider() {
    return [
      'insider' => ['scope' => PermissionScopeInterface::INSIDER_ID],
      'outsider' => ['scope' => PermissionScopeInterface::OUTSIDER_ID],
    ];
  }

}
