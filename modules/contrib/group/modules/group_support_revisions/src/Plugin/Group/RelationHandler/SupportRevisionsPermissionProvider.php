<?php

namespace Drupal\group_support_revisions\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Alters all permission providers to add revision support.
 */
class SupportRevisionsPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait {
    init as defaultInit;
  }

  /**
   * Whether the target entity type implements the RevisionableInterface.
   *
   * @var bool
   */
  protected bool $implementsRevisionableInterface;

  /**
   * Constructs a new SupportRevisionsPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    $this->defaultInit($plugin_id, $group_relation_type);
    $this->implementsRevisionableInterface = $this->entityType->entityClassImplements(RevisionableInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if ($target === 'entity') {
      switch ($operation) {
        case 'view all revisions':
          return $this->getEntityViewAllRevisionsPermission();

        case 'view revision':
          return $this->getEntityViewRevisionPermission();

        case 'revert revision':
          return $this->getEntityRevertRevisionPermission();

        case 'delete revision':
          return $this->getEntityDeleteRevisionPermission();
      }
    }

    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();

    // Instead of checking whether this specific permission provider allows for
    // a permission to exist, we check the entire decorator chain. This avoids a
    // lot of copy-pasted code to turn off or rename a permission in a decorator
    // further down the chain.
    $provider_chain = $this->groupRelationTypeManager()->getPermissionProvider($this->pluginId);

    $prefix = 'Revisions:';
    if ($name = $provider_chain->getPermission('view all revisions', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View full version history");
    }
    if ($name = $provider_chain->getPermission('view revision', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View specific entity revisions");
    }
    if ($name = $provider_chain->getPermission('revert revision', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Revert specific entity revisions");
    }
    if ($name = $provider_chain->getPermission('delete revision', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete specific entity revisions");
    }

    return $permissions;
  }

  /**
   * Gets the name of the view all revisions permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewAllRevisionsPermission() {
    if ($this->definesEntityPermissions && $this->implementsRevisionableInterface) {
      return "view all $this->pluginId entity revisions";
    }
    return FALSE;
  }

  /**
   * Gets the name of the view all revisions permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewRevisionPermission() {
    if ($this->definesEntityPermissions && $this->implementsRevisionableInterface) {
      return "view $this->pluginId entity revisions";
    }
    return FALSE;
  }

  /**
   * Gets the name of the view all revisions permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityRevertRevisionPermission() {
    if ($this->definesEntityPermissions && $this->implementsRevisionableInterface) {
      return "revert $this->pluginId entity revisions";
    }
    return FALSE;
  }

  /**
   * Gets the name of the view all revisions permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityDeleteRevisionPermission() {
    if ($this->definesEntityPermissions && $this->implementsRevisionableInterface) {
      return "delete $this->pluginId entity revisions";
    }
    return FALSE;
  }

}
