<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the scope limitations for a group role.
 *
 * @Constraint(
 *   id = "GroupRoleScope",
 *   label = @Translation("Group role scope check", context = "Validation"),
 *   type = "entity:group_role"
 * )
 */
class GroupRoleScope extends Constraint {

  /**
   * When someone attempts to create an anonymous insider group role.
   *
   * @var string
   */
  public $anonymousMemberMessage = 'Anonymous users cannot be members so you may not create an insider role for the %role global role.';

  /**
   * When a duplicate group role - global role pair is detected.
   *
   * @var string
   */
  public $duplicateScopePairMessage = 'The %group_type group type already has a group role within the @scope scope for the %role global role';

}
