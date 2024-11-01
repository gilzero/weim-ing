<?php

namespace Drupal\group;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;

/**
 * Wrapper class for a GroupRelationship entity representing a membership.
 *
 * @deprecated in group:3.2.0 and is removed from group:4.0.0. Use the static
 *   methods on \Drupal\group\Entity\GroupMembership instead.
 * @see https://www.drupal.org/node/3383363
 */
class GroupMembership implements CacheableDependencyInterface {

  /**
   * The relationship entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupRelationshipInterface
   */
  protected $groupRelationship;

  /**
   * Constructs a new GroupMembership.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The relationship entity representing the membership.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupRelationship entity that is not based on the GroupMembership plugin.
   */
  public function __construct(GroupRelationshipInterface $group_relationship) {
    if ($group_relationship->getRelationshipType()->getPluginId() == 'group_membership') {
      $this->groupRelationship = $group_relationship;
    }
    else {
      throw new \Exception('Trying to create a GroupMembership from an incompatible GroupRelationship entity.');
    }
    if (!$group_relationship instanceof GroupMembershipInterface) {
      @trigger_error('GroupRelationship entities representing memberships are expected to implement \Drupal\group\Entity\GroupMembershipInterface as of Group v3.2.0.');
    }
  }

  /**
   * Returns the fieldable GroupRelationship entity for the membership.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   *   The group relationship entity.
   */
  public function getGroupRelationship() {
    return $this->groupRelationship;
  }

  /**
   * Returns the group for the membership.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group entity.
   */
  public function getGroup() {
    return $this->groupRelationship->getGroup();
  }

  /**
   * Returns the user for the membership.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  public function getUser() {
    return $this->groupRelationship->getEntity();
  }

  /**
   * Returns the group roles for the membership.
   *
   * @param bool $include_synchronized
   *   (optional) Whether to include the synchronized roles from the outsider or
   *   insider scope. Defaults to TRUE.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles($include_synchronized = TRUE) {
    if ($this->groupRelationship instanceof GroupMembershipInterface) {
      return $this->groupRelationship->getRoles($include_synchronized);
    }

    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup(), $include_synchronized);
  }

  /**
   * Adds a group role to the membership.
   *
   * @param string $role_id
   *   The ID of the group role to add.
   */
  public function addRole(string $role_id): void {
    if ($this->groupRelationship instanceof GroupMembershipInterface) {
      $this->groupRelationship->addRole($role_id);
    }
  }

  /**
   * Removes a group role from the membership.
   *
   * @param string $role_id
   *   The ID of the group role to remove.
   */
  public function removeRole(string $role_id): void {
    if ($this->groupRelationship instanceof GroupMembershipInterface) {
      $this->groupRelationship->removeRole($role_id);
    }
  }

  /**
   * Checks whether the member has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the member has the requested permission.
   */
  public function hasPermission($permission) {
    if ($this->groupRelationship instanceof GroupMembershipInterface) {
      return $this->groupRelationship->hasPermission($permission);
    }

    return $this->groupPermissionChecker()->hasPermissionInGroup($permission, $this->getUser(), $this->getGroup());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->groupRelationship->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->groupRelationship->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->groupRelationship->getCacheMaxAge();
  }

  /**
   * Gets the group permission checker.
   *
   * @return \Drupal\group\Access\GroupPermissionCheckerInterface
   *   The group permission checker service.
   */
  protected function groupPermissionChecker() {
    return \Drupal::service('group_permission.checker');
  }

}
