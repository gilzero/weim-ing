<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\CalculatedPermissionsItem as CoreCalculatedPermissionsItem;
use Drupal\Core\Session\CalculatedPermissionsItemInterface as CoreCalculatedPermissionsItemInterface;

/**
 * Represents a single entry for the calculated permissions.
 *
 * @see \Drupal\flexible_permissions\ChainPermissionCalculator
 */
class CalculatedPermissionsItem implements CalculatedPermissionsItemInterface {

  /**
   * The scope name.
   *
   * @var string
   */
  protected $scope;

  /**
   * The identifier.
   *
   * @var string|int
   */
  protected $identifier;

  /**
   * The permission names.
   *
   * @var string[]
   */
  protected $permissions;

  /**
   * Whether this entry grants admin rights for the given scope.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * Constructs a new CalculatedPermissionsItem.
   *
   * @param string $scope
   *   The scope name.
   * @param string|int $identifier
   *   The identifier within the scope.
   * @param string[] $permissions
   *   The permission names.
   * @param bool $is_admin
   *   (optional) Whether the item grants admin privileges.
   */
  public function __construct($scope, $identifier, $permissions, $is_admin = FALSE) {
    $this->scope = $scope;
    $this->identifier = $identifier;
    $this->permissions = $is_admin ? [] : array_unique($permissions);
    $this->isAdmin = $is_admin;
  }

  /**
   * {@inheritdoc}
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    return $this->isAdmin;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->isAdmin() || in_array($permission, $this->permissions, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function toCore(): CoreCalculatedPermissionsItemInterface {
    return new CoreCalculatedPermissionsItem(
      $this->getPermissions(),
      $this->isAdmin(),
      $this->getScope(),
      $this->getIdentifier()
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function fromCore(CoreCalculatedPermissionsItemInterface $core_object): self {
    return new self(
      $core_object->getScope(),
      $core_object->getIdentifier(),
      $core_object->getPermissions(),
      $core_object->isAdmin()
    );
  }

}
