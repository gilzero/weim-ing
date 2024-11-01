<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a group role is assigned to a membership even though it can't be.
 *
 * @Constraint(
 *   id = "GroupRoleAssigned",
 *   label = @Translation("Group role assignment check", context = "Validation"),
 *   type = "entity:group_role"
 * )
 */
class GroupRoleAssigned extends Constraint {

  /**
   * When a group role is already assigned and put in a synchronized scope.
   *
   * @var string
   */
  public $alreadyAssignedMessage = 'Cannot save this group role in the %scope scope as it has already been assigned to individual members.';

}
