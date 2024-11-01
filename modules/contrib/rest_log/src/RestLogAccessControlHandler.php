<?php

namespace Drupal\rest_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the rest_log entity type.
 *
 * @see \Drupal\rest_log\Entity\RestLog
 */
class RestLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    if (in_array($operation, ['delete', 'view'])) {
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'access rest log list'));
    }
    return $access;
  }

}
