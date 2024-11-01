<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface to alter the final calculated permissions.
 */
// phpcs:ignore
interface PermissionCalculatorAlterInterfaceV2 {

  /**
   * Alter the permissions after all calculators have finished building them.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to alter the permissions.
   * @param string $scope
   *   The scope to alter the permissions for.
   * @param \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions
   *   The completely built calculated permissions.
   */
  public function alterPermissions(AccountInterface $account, $scope, RefinableCalculatedPermissionsInterface $calculated_permissions);

}
