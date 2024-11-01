<?php

declare(strict_types=1);

namespace Drupal\Tests\group\Traits;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides common helper methods for Group module tests.
 */
trait GroupTestTrait {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The created group entity.
   */
  protected function createGroup(array $values = []): GroupInterface {
    $storage = $this->entityTypeManager()->getStorage('group');
    $group = $storage->create($values + [
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group;
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The created group type entity.
   */
  protected function createGroupType(array $values = []): GroupTypeInterface {
    $storage = $this->entityTypeManager()->getStorage('group_type');
    $group_type = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'creator_wizard' => FALSE,
    ]);
    $storage->save($group_type);
    return $group_type;
  }

  /**
   * Creates a group role.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The created group role entity.
   */
  protected function createGroupRole(array $values = []): GroupRoleInterface {
    $storage = $this->entityTypeManager()->getStorage('group_role');
    $group_role = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_role);
    return $group_role;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager(): EntityTypeManagerInterface {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

}
