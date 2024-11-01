<?php

namespace Drupal\flexible_permissions_test;

use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\PermissionCalculatorAlterInterfaceV2;
use Drupal\flexible_permissions\PermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;

/**
 * Flexible Permissions policy.
 */
class OldAccessPolicy implements PermissionCalculatorInterface, PermissionCalculatorAlterInterfaceV2 {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    return (new RefinableCalculatedPermissions())->addItem(new CalculatedPermissionsItem(
      'flexible_permissions_test',
      'flexible_permissions_test',
      ['baz', 'foobar']
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    $item = $calculated_permissions->getItem('flexible_permissions_test', 'flexible_permissions_test');
    $calculated_permissions->removeItem('flexible_permissions_test', 'flexible_permissions_test');
    $calculated_permissions->addItem(new CalculatedPermissionsItem(
      'flexible_permissions_test',
      'flexible_permissions_test',
      array_diff($item->getPermissions(), ['bar'])
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return [];
  }

}
