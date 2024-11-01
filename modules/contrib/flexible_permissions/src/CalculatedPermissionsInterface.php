<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\CalculatedPermissionsInterface as CoreCalculatedPermissionsInterface;

/**
 * Defines the calculated permissions interface.
 */
interface CalculatedPermissionsInterface extends CacheableDependencyInterface {

  /**
   * Retrieves a single calculated permission item from a given scope.
   *
   * @param string $scope
   *   The scope name to retrieve the item for.
   * @param string|int $identifier
   *   The scope identifier to retrieve the item for.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface|false
   *   The calculated permission item or FALSE if it could not be found.
   */
  public function getItem($scope, $identifier);

  /**
   * Retrieves all of the calculated permission items, regardless of scope.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items.
   */
  public function getItems();

  /**
   * Retrieves all of the scopes that have items for them.
   *
   * @return string[]
   *   The scope names that are in use.
   */
  public function getScopes();

  /**
   * Retrieves all of the calculated permission items for the given scope.
   *
   * @param string $scope
   *   The scope name to retrieve the items for.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items for the given scope.
   */
  public function getItemsByScope($scope);

  /**
   * Converts an FP version into an Access Policy API version.
   *
   * @return \Drupal\Core\Session\CalculatedPermissionsInterface
   *   The Access Policy API counterpart.
   */
  public function toCore(): CoreCalculatedPermissionsInterface;

  /**
   * Converts an Access Policy API version into an FP version.
   *
   * @param \Drupal\Core\Session\CalculatedPermissionsInterface $core_object
   *   The Drupal core version of this object.
   *
   * @return self
   *   The Flexible Permissions counterpart.
   */
  public static function fromCore(CoreCalculatedPermissionsInterface $core_object): self;

}
