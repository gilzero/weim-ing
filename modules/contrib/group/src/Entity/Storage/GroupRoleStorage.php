<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group role entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group role entities based on user and group information.
 */
class GroupRoleStorage extends ConfigEntityStorage implements GroupRoleStorageInterface {

  /**
   * Static cache of a user's group role IDs.
   *
   * @var array
   */
  protected $userGroupRoleIds = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GroupMembershipLoaderInterface $groupMembershipLoader,
    EntityTypeInterface $entity_type,
    ConfigFactoryInterface $config_factory,
    UuidInterface $uuid_service,
    LanguageManagerInterface $language_manager,
    MemoryCacheInterface $memory_cache,
    protected Connection $database,
  ) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group.membership_loader'),
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    // Entity storage does not validate constraints by default.
    $violations = $entity->getTypedData()->validate();
    foreach ($violations as $violation) {
      throw new EntityMalformedException($violation->getMessage());
    }

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUserAndGroup(AccountInterface $account, GroupInterface $group, $include_synchronized = TRUE) {
    $uid = $account->id();
    $gid = $group->id();
    $key = $include_synchronized ? 'include' : 'exclude';

    if (!isset($this->userGroupRoleIds[$uid][$gid][$key])) {
      $ids = [];

      // Get the IDs from the 'group_roles' field, without loading the roles.
      if ($membership = $this->groupMembershipLoader->load($group, $account)) {
        $ids = array_column($membership->getGroupRelationship()->get('group_roles')->getValue(), 'target_id');
      }

      if ($include_synchronized) {
        $roles = $account->getRoles();
        $query = $this->getQuery()
          ->condition('scope', $membership ? PermissionScopeInterface::INSIDER_ID : PermissionScopeInterface::OUTSIDER_ID)
          ->condition('global_role', $roles, 'IN')
          ->condition('group_type', $group->bundle());
        $ids = array_merge($ids, $query->accessCheck(FALSE)->execute());
      }

      $this->userGroupRoleIds[$uid][$gid][$key] = $ids;
    }

    return $this->loadMultiple($this->userGroupRoleIds[$uid][$gid][$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetUserGroupRoleCache(AccountInterface $account, ?GroupInterface $group = NULL) {
    $uid = $account->id();
    if (isset($group)) {
      unset($this->userGroupRoleIds[$uid][$group->id()]);
    }
    else {
      unset($this->userGroupRoleIds[$uid]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasMembershipReferences(array $group_role_ids): bool {
    return (bool) $this->database->select('group_relationship__group_roles', 'gr')
      ->condition('gr.group_roles_target_id', $group_role_ids, 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMembershipReferences(array $group_role_ids): void {
    $this->database->delete('group_relationship__group_roles')
      ->condition('group_roles_target_id', $group_role_ids, 'IN')
      ->execute();

    $this->userGroupRoleIds = [];
    $this->entityTypeManager->getStorage('group_relationship')->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(?array $ids = NULL) {
    parent::resetCache($ids);
    $this->userGroupRoleIds = [];
  }

}
