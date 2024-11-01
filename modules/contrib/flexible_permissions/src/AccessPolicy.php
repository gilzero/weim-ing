<?php

declare(strict_types=1);

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface as CoreRefinableCalculatedPermissionsInterface;

/**
 * Converts FP policies into core ones.
 */
class AccessPolicy extends AccessPolicyBase {

  public function __construct(protected ChainPermissionCalculatorInterface $chainCalculator) {}

  /**
   * The last checked scope.
   *
   * @var string
   */
  protected string $lastCheckedScope;

  /**
   * {@inheritdoc}
   */
  public function applies(string $scope): bool {
    $this->lastCheckedScope = $scope;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): CoreRefinableCalculatedPermissionsInterface {
    $calculated_permissions = RefinableCalculatedPermissions::fromCore(parent::calculatePermissions($account, $scope));
    foreach ($this->chainCalculator->getCalculators() as $calculator) {
      $calculated_permissions->merge($calculator->calculatePermissions($account, $scope));
    }
    return $calculated_permissions->toCore();
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, CoreRefinableCalculatedPermissionsInterface $calculated_permissions): void {
    parent::alterPermissions($account, $scope, $calculated_permissions);
    $converted = RefinableCalculatedPermissions::fromCore($calculated_permissions);
    $converted->disableBuildMode();

    foreach ($this->chainCalculator->getCalculators() as $calculator) {
      if ($calculator instanceof PermissionCalculatorAlterInterfaceV2) {
        $calculator->alterPermissions($account, $scope, $converted);
      }
      elseif ($calculator instanceof PermissionCalculatorAlterInterface) {
        $calculator->alterPermissions($converted);
      }
    }

    $calculated_permissions->removeItems();
    $calculated_permissions->merge($converted->toCore());
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    $cache_context_sets = [];
    foreach ($this->chainCalculator->getCalculators() as $calculator) {
      $cache_context_sets[] = $calculator->getPersistentCacheContexts($this->lastCheckedScope);
    }
    return array_merge(...$cache_context_sets);
  }

}
