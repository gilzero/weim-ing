<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for group role entity storage classes.
 */
interface GroupRoleStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Retrieves all GroupRole entities for a user within a group.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to load the group role entities for.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to find the user's role entities in.
   * @param bool $include_synchronized
   *   (optional) Whether to include the synchronized roles from the outsider or
   *   insider scope. Defaults to TRUE.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles matching the criteria.
   */
  public function loadByUserAndGroup(AccountInterface $account, GroupInterface $group, $include_synchronized = TRUE);

  /**
   * Resets the internal, static cache used by ::loadByUserAndGroup().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to reset the cached group roles for.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   (optional) The group to reset the user's cached group roles for. Leave
   *   blank to reset the user's roles in all groups.
   */
  public function resetUserGroupRoleCache(AccountInterface $account, ?GroupInterface $group = NULL);

  /**
   * Checks if group roles have membership references.
   *
   * @param string[] $group_role_ids
   *   The list of group role IDs being checked.
   *
   * @return bool
   *   Whether any of the group roles are being referenced by a membership.
   */
  public function hasMembershipReferences(array $group_role_ids): bool;

  /**
   * Deletes group role membership references.
   *
   * @param string[] $group_role_ids
   *   The list of group role IDs being deleted. The storage should
   *   remove member references to this role.
   */
  public function deleteMembershipReferences(array $group_role_ids): void;

}
