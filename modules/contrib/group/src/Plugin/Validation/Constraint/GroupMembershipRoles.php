<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the roles assigned to a group membership.
 *
 * @Constraint(
 *   id = "GroupMembershipRoles",
 *   label = @Translation("Group membership roles check", context = "Validation"),
 *   type = "entity:group_relationship"
 * )
 */
class GroupMembershipRoles extends Constraint {

  /**
   * When a role does not exist.
   *
   * @var string
   */
  public $roleDoesNotExistMessage = 'Could not assign role with ID @role_id: Role does not exist.';

  /**
   * When a role belongs to different group type.
   *
   * @var string
   */
  public $roleDifferentGroupTypeMessage = 'Could not assign role with ID @role_id: Role belongs to a different group type.';

  /**
   * When a role belongs to a non-individual scope.
   *
   * @var string
   */
  public $roleNonIndividualScopeMessage = 'Could not assign role with ID @role_id: Role does not belong to the individual scope.';

}
