<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines the access control handler for the group type entity type.
 *
 * @see \Drupal\group\Entity\GroupType
 */
class GroupTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GroupTypeInterface);

    // Group types have no 'view' route but may be used in views to show what
    // type a group is. We therefore allow 'view' access so field formatters
    // such as entity_reference_label will work.
    if ($operation == 'view') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }

    $access = parent::checkAccess($entity, $operation, $account);
    assert($access instanceof RefinableCacheableDependencyInterface);

    if ($operation == 'delete') {
      return $access->addCacheableDependency($entity);
    }

    return $access;
  }

}
