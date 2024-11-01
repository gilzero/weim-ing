<?php

namespace Drupal\flexible_permissions;

/**
 * Defines an interface to alter the final calculated permissions.
 */
interface PermissionCalculatorAlterInterface {

  /**
   * Alter the permissions after all calculators have finished building them.
   *
   * @param \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions
   *   The completely built calculated permissions.
   */
  public function alterPermissions(RefinableCalculatedPermissionsInterface $calculated_permissions);

}
