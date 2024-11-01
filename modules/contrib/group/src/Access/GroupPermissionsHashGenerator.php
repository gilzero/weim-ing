<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Generates and caches the permissions hash for a group membership.
 */
class GroupPermissionsHashGenerator implements GroupPermissionsHashGeneratorInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a GroupPermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $static, GroupPermissionCalculatorInterface $permission_calculator, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->privateKey = $private_key;
    $this->static = $static;
    $this->groupPermissionCalculator = $permission_calculator;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function generateHash(AccountInterface $account) {
    // We can use a simple per-user static cache here because we already cache
    // the permissions more efficiently in the group permission calculator. On
    // top of that, there is only a tiny chance of a hash being generated for
    // more than one account during a single request.
    $cid = 'group_permissions_hash_' . $account->id();

    // Retrieve the hash from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    // Otherwise hash the permissions and store them in the static cache.
    else {
      $calculated_permissions = $this->groupPermissionCalculator->calculateFullPermissions($account);

      $permissions = [];
      foreach ($calculated_permissions->getItems() as $item) {
        // If the calculated permissions item grants admin rights, we can
        // simplify the entry by setting it to 'is-admin' rather than a list of
        // permissions. This will ensure admins for the given scope item always
        // match even if their list of permissions differs.
        if ($item->isAdmin()) {
          $item_permissions = 'is-admin';
        }
        else {
          $item_permissions = $item->getPermissions();

          // Sort the permissions by name to ensure we don't get mismatching
          // hashes for people with the same permissions, just because the order
          // of the permissions happened to differ.
          sort($item_permissions);
        }

        $permissions[$item->getScope()][$item->getIdentifier()] = $item_permissions;
      }

      // Sort the result by key to ensure we don't get mismatching hashes for
      // people with the same permissions, just because the order of the keys
      // happened to differ.
      ksort($permissions);
      foreach ($permissions as &$scope_permissions) {
        ksort($scope_permissions);
      }

      // If we have any synchronized permissions, we need to make sure that the
      // hash is also based on your membership IDs. This leads to a poorer hit
      // ratio, but if we don't add this information then we might return the
      // same hash for two accounts that should see different results.
      //
      // E.g.: Both Alice and Bob have insider permissions to view groups of
      // type Fruit but Alice is a member of Apple and Bob of Banana. This means
      // that Alice should only see Apple in a list of groups and Bob should
      // only see Banana. However, unless we add the IDs of groups they belong
      // to to the hash, we would cache the wrong list for whoever comes second.
      //
      // To somewhat mitigate the increased cache variance, we only look up
      // membership IDs of those group types that you have synchronized
      // permissions for.
      $group_type_ids = array_unique(array_merge(
        array_keys($permissions[PermissionScopeInterface::INSIDER_ID] ?? []),
        array_keys($permissions[PermissionScopeInterface::OUTSIDER_ID] ?? [])
      ));

      if ($group_type_ids) {
        $grt_storage = $this->entityTypeManager->getStorage('group_relationship_type');
        assert($grt_storage instanceof GroupRelationshipTypeStorageInterface);

        $group_relationship_type_ids = array_map(
          fn($group_type_id) => $grt_storage->getRelationshipTypeId($group_type_id, 'group_membership'),
          $group_type_ids
        );

        $permissions['membership_group_ids'] = $this->database->select('group_relationship_field_data', 'grfd')
          ->fields('grfd', ['gid'])
          ->condition('grfd.type', $group_relationship_type_ids, 'IN')
          ->condition('grfd.entity_id', $account->id())
          ->orderBy('grfd.gid')
          ->execute()
          ->fetchCol();
      }

      $hash = $this->hash(serialize($permissions));
      $this->static->set($cid, $hash, Cache::PERMANENT, $calculated_permissions->getCacheTags());
      return $hash;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(AccountInterface $account) {
    return CacheableMetadata::createFromObject($this->groupPermissionCalculator->calculateFullPermissions($account));
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
