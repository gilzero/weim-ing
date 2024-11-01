<?php

namespace Drupal\flexible_permissions;

/**
 * Trait for \Drupal\flexible_permissions\CalculatedPermissionsInterface.
 */
trait CalculatedPermissionsTrait {

  /**
   * A list of calculated permission items, keyed by scope and identifier.
   *
   * @var array
   */
  protected $items = [];

  /**
   * {@inheritdoc}
   */
  public function getItem($scope, $identifier) {
    return $this->items[$scope][$identifier] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $items = [];
    foreach ($this->items as $scope_items) {
      foreach ($scope_items as $item) {
        $items[] = $item;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopes() {
    return array_keys($this->items);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsByScope($scope) {
    return isset($this->items[$scope])
      ? array_values($this->items[$scope])
      : [];
  }

}
