<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\danse\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the danse event entity type.
 */
final class EventAccess extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The DANSE query service.
   *
   * @var \Drupal\danse\Query
   */
  protected Query $query;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, Query $query) {
    parent::__construct($entity_type);
    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): EventAccess {
    return new EventAccess(
      $entity_type,
      $container->get('danse.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface {
    if ($entity instanceof EventInterface && $this->query->findEventNotificationsForCurrentUser($entity)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
