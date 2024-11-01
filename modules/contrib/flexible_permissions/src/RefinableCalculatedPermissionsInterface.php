<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Defines the calculated permissions interface.
 */
interface RefinableCalculatedPermissionsInterface extends RefinableCacheableDependencyInterface, CalculatedPermissionsInterface {

  /**
   * Disables build mode.
   *
   * When build mode is on, which is the default state, only new items can be
   * added. Only after build mode is disabled can items be removed or replaced.
   *
   * @internal
   */
  public function disableBuildMode();

  /**
   * Adds a calculated permission item.
   *
   * @param \Drupal\flexible_permissions\CalculatedPermissionsItemInterface $item
   *   The calculated permission item.
   * @param bool $overwrite
   *   (optional) Whether to overwrite an item if there already is one for the
   *   given identifier within the scope. Defaults to FALSE, meaning a merge
   *   will take place instead. Does nothing if build mode is still enabled.
   *
   * @return $this
   */
  public function addItem(CalculatedPermissionsItemInterface $item, $overwrite = FALSE);

  /**
   * Removes a single calculated permission item from a given scope.
   *
   * Does nothing if build mode is still enabled.
   *
   * @param string $scope
   *   The scope name to remove the item from.
   * @param string|int $identifier
   *   The scope identifier to remove the item from.
   *
   * @return $this
   */
  public function removeItem($scope, $identifier);

  /**
   * Removes all of the calculated permission items, regardless of scope.
   *
   * Does nothing if build mode is still enabled.
   *
   * @return $this
   */
  public function removeItems();

  /**
   * Removes all of the calculated permission items for the given scope.
   *
   * Does nothing if build mode is still enabled.
   *
   * @param string $scope
   *   The scope name to remove the items for.
   *
   * @return $this
   */
  public function removeItemsByScope($scope);

  /**
   * Merge another calculated permissions object into this one.
   *
   * This merges (not replaces) all permissions and cacheable metadata.
   *
   * @param \Drupal\flexible_permissions\CalculatedPermissionsInterface $other
   *   The other calculated permissions object to merge into this one.
   *
   * @return $this
   */
  public function merge(CalculatedPermissionsInterface $other);

}
