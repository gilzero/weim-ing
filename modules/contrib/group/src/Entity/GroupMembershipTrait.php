<?php

namespace Drupal\group\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;
use Drupal\group\Plugin\Validation\Constraint\GroupMembershipRoles;

/**
 * Functionality trait for a group_membership bundle class.
 */
trait GroupMembershipTrait {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // @todo 4.x.x Validate all constraints in parent preSave()?.
    $violations = $this->validate();
    foreach ($violations as $violation) {
      // To not break BC we only throw exceptions for our constraint.
      if (!$violation->getConstraint() instanceof GroupMembershipRoles) {
        continue;
      }
      throw new EntityMalformedException($violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($include_synchronized = TRUE) {
    $group_role_storage = $this->entityTypeManager()->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($this->getEntity(), $this->getGroup(), $include_synchronized);
  }

  /**
   * {@inheritdoc}
   */
  public function addRole(string $role_id): void {
    // Do nothing if the role is already present.
    foreach ($this->group_roles as $group_role_ref) {
      if ($group_role_ref->target_id === $role_id) {
        return;
      }
    }

    $this->group_roles[] = $role_id;
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole(string $role_id): void {
    foreach ($this->group_roles as $key => $group_role_ref) {
      if ($group_role_ref->target_id === $role_id) {
        $this->group_roles->removeItem($key);
      }
    }
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->getGroup()->hasPermission($permission, $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadSingle(GroupInterface $group, AccountInterface $account) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
    $cache_backend = \Drupal::service('cache.group_memberships_chained');
    assert($cache_backend instanceof CacheBackendInterface);

    // Get the same CID as ::loadByUser without roles.
    $cid = static::createCacheId([
      'entity_id' => $account->id(),
      'roles' => 'any-roles',
    ]);

    if ($cache = $cache_backend->get($cid)) {
      if (empty($cache->data) || empty($cache->data[$group->id()])) {
        return FALSE;
      }
      return $storage->load($cache->data[$group->id()]);
    }

    // Prime the cache by loading all of the user's memberships. For now, it
    // seems like there's a higher likelihood of us needing all of them rather
    // than a few individual ones. If we load them one by one, we have to fire
    // multiple entity queries, which incurs a rather big performance hit.
    //
    // We choose to prime the cache by calling ::loadByUser over ::loadByGroup
    // because a group could have a large amount of members. If you have a user
    // with a large amount of memberships, you should check whether you can
    // optimize this by making better use of insider and outsider roles.
    //
    // If loading all of the memberships turns out to happen quite often when
    // we do, in fact, only need one or two, then we should revisit this.
    $memberships = static::loadByUser($account);
    foreach ($memberships as $membership) {
      assert($membership instanceof GroupRelationshipInterface);
      if ($membership->getGroupId() === $group->id()) {
        return $membership;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByGroup(GroupInterface $group, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
    $cache_backend = \Drupal::service('cache.group_memberships_chained');
    assert($cache_backend instanceof CacheBackendInterface);

    $cid = static::createCacheId([
      'gid' => $group->id(),
      'roles' => $roles ?? 'any-roles',
    ]);

    if ($cache = $cache_backend->get($cid)) {
      if (empty($cache->data)) {
        return [];
      }
      return $storage->loadMultiple($cache->data);
    }

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $cacheability = (new CacheableMetadata())
      ->addCacheTags(['group_relationship_list:plugin:group_membership:group:' . $group->id()]);

    $cache_backend->set($cid, $ids = $query->execute(), $cacheability->getCacheMaxAge(), $cacheability->getCacheTags());
    return $storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUser(?AccountInterface $account = NULL, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
    $cache_backend = \Drupal::service('cache.group_memberships_chained');
    assert($cache_backend instanceof CacheBackendInterface);

    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    $cid = static::createCacheId([
      'entity_id' => $account->id(),
      'roles' => $roles ?? 'any-roles',
    ]);

    if ($cache = $cache_backend->get($cid)) {
      if (empty($cache->data)) {
        return [];
      }
      return $storage->loadMultiple($cache->data);
    }

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $cacheability = (new CacheableMetadata())
      ->addCacheTags(['group_relationship_list:plugin:group_membership:entity:' . $account->id()]);

    // Cache the IDs by group ID, so we can use this cache in ::loadSingle().
    $cached_ids = [];
    foreach ($memberships = $storage->loadMultiple($query->execute()) as $membership) {
      assert($membership instanceof GroupRelationshipInterface);
      $cached_ids[$membership->getGroupId()] = $membership->id();
    }
    $cache_backend->set($cid, $cached_ids, $cacheability->getCacheMaxAge(), $cacheability->getCacheTags());
    return $memberships;
  }

  /**
   * Creates a cache ID based on provided values.
   *
   * @param array<string, mixed> $values
   *   A group of values that were used to filter, keyed by an identifier.
   *
   * @return string
   *   The cache ID.
   */
  protected static function createCacheId(array $values) {
    ksort($values);

    $cid_parts = ['group_memberships'];
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        sort($value);
        $value = implode('.', $value);
      }
      $cid_parts[] = $key . '[' . $value . ']';
    }

    return implode(':', $cid_parts);
  }

}
