<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Session\CalculatedPermissions as CoreCalculatedPermissions;
use Drupal\Core\Session\CalculatedPermissionsInterface as CoreCalculatedPermissionsInterface;

/**
 * Represents a calculated set of permissions with cacheable metadata.
 *
 * @see \Drupal\flexible_permissions\ChainPermissionCalculator
 */
class CalculatedPermissions implements CalculatedPermissionsInterface {

  use CacheableDependencyTrait;
  use CalculatedPermissionsTrait;

  /**
   * Constructs a new CalculatedPermissions.
   *
   * @param \Drupal\flexible_permissions\CalculatedPermissionsInterface $source
   *   The calculated permission to create a value object from.
   */
  public function __construct(CalculatedPermissionsInterface $source) {
    foreach ($source->getItems() as $item) {
      $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    }
    $this->setCacheability($source);

    // The (persistent) cache contexts attached to the permissions are only
    // used internally to store the permissions in the VariationCache. We strip
    // these cache contexts when the calculated permissions get converted into a
    // value object here so that they will never bubble up by accident.
    $this->cacheContexts = [];
  }

  /**
   * {@inheritdoc}
   */
  public function toCore(): CoreCalculatedPermissionsInterface {
    $converted = (new RefinableCalculatedPermissions())->merge($this)->toCore();
    return new CoreCalculatedPermissions($converted);
  }

  /**
   * {@inheritdoc}
   */
  public static function fromCore(CoreCalculatedPermissionsInterface $core_object): self {
    $converted = RefinableCalculatedPermissions::fromCore($core_object);
    return new self($converted);
  }

}
