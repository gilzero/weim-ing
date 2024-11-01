<?php

namespace Drupal\flexible_permissions_test;

use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;

/**
 * Access Policy API policy.
 */
class NewAccessPolicy extends AccessPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function applies(string $scope): bool {
    return $scope === 'flexible_permissions_test';
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    return parent::calculatePermissions($account, $scope)->addItem(new CalculatedPermissionsItem(
      ['foo', 'bar'],
      FALSE,
      'flexible_permissions_test',
      'flexible_permissions_test',
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions): void {
    parent::alterPermissions($account, $scope, $calculated_permissions);
    $item = $calculated_permissions->getItem('flexible_permissions_test', 'flexible_permissions_test');
    $calculated_permissions->removeItem('flexible_permissions_test', 'flexible_permissions_test');
    $calculated_permissions->addItem(new CalculatedPermissionsItem(
      array_diff($item->getPermissions(), ['baz']),
      FALSE,
      'flexible_permissions_test',
      'flexible_permissions_test',
    ));
  }

}
