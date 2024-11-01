<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership as NewGroupMembership;

/**
 * Loader for GroupRelationship entities using the 'group_membership' plugin.
 *
 * @deprecated in group:3.2.0 and is removed from group:4.0.0. Use the static
 *   methods on \Drupal\group\Entity\GroupMembership instead.
 * @see https://www.drupal.org/node/3383363
 */
class GroupMembershipLoader implements GroupMembershipLoaderInterface {

  /**
   * Constructs a new GroupMembershipLoader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {}

  /**
   * Wraps GroupRelationship entities in a GroupMembership object.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface[] $entities
   *   An array of GroupRelationship entities to wrap.
   *
   * @return \Drupal\group\GroupMembership[]
   *   A list of GroupMembership wrapper objects.
   */
  protected function wrapGroupRelationshipEntities($entities) {
    $group_memberships = [];
    foreach ($entities as $group_relationship) {
      $group_memberships[] = new GroupMembership($group_relationship);
    }
    return $group_memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    if ($group_membership = NewGroupMembership::loadSingle($group, $account)) {
      $group_memberships = $this->wrapGroupRelationshipEntities([$group_membership]);
      return reset($group_memberships);
    }
    return $group_membership;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL) {
    return $this->wrapGroupRelationshipEntities(NewGroupMembership::loadByGroup($group, $roles));
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(?AccountInterface $account = NULL, $roles = NULL) {
    return $this->wrapGroupRelationshipEntities(NewGroupMembership::loadByUser($account, $roles));
  }

}
