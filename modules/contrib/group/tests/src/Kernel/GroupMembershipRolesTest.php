<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group\Plugin\Validation\Constraint\GroupMembershipRoles;
use Drupal\user\RoleInterface;

/**
 * Tests for the GroupMembershipRoles constraint.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Plugin\Validation\Constraint\GroupMembershipRoles
 */
class GroupMembershipRolesTest extends GroupKernelTestBase {

  /**
   * Tests the role validation.
   *
   * @covers ::validate
   */
  public function testValidate(): void {
    $grt_storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($grt_storage instanceof GroupRelationshipTypeStorageInterface);

    $gr_storage = $this->entityTypeManager()->getStorage('group_relationship');
    assert($gr_storage instanceof GroupRelationshipStorageInterface);

    $group_type = $this->createGroupType();
    $group_type_id = $group_type->id();

    $group_relationship = $gr_storage->create([
      'type' => $grt_storage->getRelationshipTypeId($group_type_id, 'group_membership'),
      'gid' => $this->createGroup(['type' => $group_type_id])->id(),
      'entity_id' => $this->createUser()->id(),
      'group_roles' => [],
    ]);

    // Verify the absence of violations in a newly created membership.
    $violations = $group_relationship->validate();
    $this->filterViolations($violations);
    $this->assertCount(0, $violations, 'A freshly created group membership has no violations.');

    // Try to add a non-existent role to the relationship.
    $group_relationship->set('group_roles', ['banana']);

    $violations = $group_relationship->validate();
    $this->filterViolations($violations);
    $this->assertCount(1, $violations, 'Adding a non-existent role triggers a violation.');
    $message = new TranslatableMarkup('Could not assign role with ID banana: Role does not exist.');
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());

    // Try to add a role from another group type.
    $group_role_id = $this->createGroupRole([
      'group_type' => $this->createGroupType()->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ])->id();
    $group_relationship->set('group_roles', [$group_role_id]);

    $violations = $group_relationship->validate();
    $this->filterViolations($violations);
    $this->assertCount(1, $violations, 'Adding a role from another group type triggers a violation.');
    $message = new TranslatableMarkup('Could not assign role with ID @role_id: Role belongs to a different group type.', ['@role_id' => $group_role_id]);
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());

    // Try to add an insider role.
    $group_role_id = $this->createGroupRole([
      'group_type' => $group_type_id,
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ])->id();
    $group_relationship->set('group_roles', [$group_role_id]);

    $violations = $group_relationship->validate();
    $this->filterViolations($violations);
    $this->assertCount(1, $violations, 'Adding an insider role triggers a violation.');
    $message = new TranslatableMarkup('Could not assign role with ID @role_id: Role does not belong to the individual scope.', ['@role_id' => $group_role_id]);
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());
  }

  /**
   * Filters out violations that did not come from our subject constraint.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   The list of violations to filter.
   */
  protected function filterViolations(EntityConstraintViolationListInterface $violations): void {
    foreach ($violations as $offset => $violation) {
      if (!$violation->getConstraint() instanceof GroupMembershipRoles) {
        $violations->offsetUnset($offset);
      }
    }
  }

}
