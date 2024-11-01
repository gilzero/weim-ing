<?php

namespace Drupal\group\Entity;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a Group membership bundle entity.
 *
 * @ingroup group
 */
interface GroupMembershipInterface extends GroupRelationshipInterface {

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
  public function getRoles($include_synchronized = TRUE);

  /**
   * Adds a group role to the membership.
   *
   * @param string $role_id
   *   The ID of the group role to add.
   */
  public function addRole(string $role_id): void;

  /**
   * Removes a group role from the membership.
   *
   * @param string $role_id
   *   The ID of the group role to remove.
   */
  public function removeRole(string $role_id): void;

  /**
   * Checks whether the member has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the member has the requested permission.
   */
  public function hasPermission($permission);

  /**
   * Loads a single group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the membership from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the membership for.
   *
   * @return \Drupal\group\Entity\GroupMembershipInterface|false
   *   The loaded group membership or FALSE if none was found.
   */
  public static function loadSingle(GroupInterface $group, AccountInterface $account);

  /**
   * Loads all memberships for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the memberships from.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\Entity\GroupMembershipInterface[]
   *   The loaded group memberships matching the criteria.
   */
  public static function loadByGroup(GroupInterface $group, $roles = NULL);

  /**
   * Loads all memberships for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user to load the membership for. Leave blank to load the
   *   memberships of the currently logged in user.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\Entity\GroupMembershipInterface[]
   *   The loaded group memberships matching the criteria.
   */
  public static function loadByUser(?AccountInterface $account = NULL, $roles = NULL);

}
