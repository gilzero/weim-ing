<?php

namespace Drupal\danse;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\danse\Entity\EventInterface;

/**
 * Provides common database queries as a service.
 */
class Query {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructs the query service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * Returns all event notification for the current user.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The DANSE event.
   *
   * @return \Drupal\danse\Entity\NotificationInterface[]
   *   List of notifications.
   */
  public function findEventNotificationsForCurrentUser(EventInterface $event): array {
    $notifications = [];
    try {
      /**
       * @var \Drupal\danse\Entity\NotificationInterface[] $notifications
       */
      $notifications = $this->entityTypeManager->getStorage('danse_notification')->loadByProperties([
        'event' => $event->id(),
        'uid' => $this->currentUser->id(),
      ]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
    return $notifications;
  }

  /**
   * Returns all notifications for the current user related to a given payload.
   *
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   *
   * @return \Drupal\danse\Entity\NotificationInterface[]
   *   List of notifications.
   */
  public function findNotificationsForCurrentUser(PayloadInterface $payload): array {
    $reference = $payload->getEventReference();
    $query = $this->database->select('danse_notification', 'n');
    $query->join('danse_event', 'e', 'n.event = e.id');
    $ids = $query
      ->fields('n', ['id'])
      ->condition('n.uid', $this->currentUser->id())
      ->condition('n.seen', 0)
      ->condition('e.reference', $reference)
      ->execute()
      ->fetchCol();
    $notifications = [];
    if (!empty($ids)) {
      try {
        /**
         * @var \Drupal\danse\Entity\NotificationInterface[] $notifications
         */
        $notifications = $this->entityTypeManager->getStorage('danse_notification')
          ->loadMultiple($ids);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        // @todo Log this exception.
      }
    }
    return $notifications;
  }

  /**
   * Find already existing notifications for a given user.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The DANSE event.
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\danse\Entity\NotificationInterface[]
   *   List of notifications.
   */
  public function findSimilarEventNotifications(EventInterface $event, int $uid): array {
    $notifications = [];
    if ($payload = $event->getPayload()) {
      $query = $this->database->select('danse_notification', 'n');
      $query->join('danse_event', 'e', 'n.event = e.id');
      $ids = $query
        ->fields('n', ['id'])
        ->condition('n.uid', $uid)
        ->condition('n.delivered', 0)
        ->condition('n.seen', 0)
        ->condition('n.redundant', 0)
        ->condition('e.topic', $event->getTopic())
        ->condition('e.reference', $payload->getEventReference())
        ->execute()
        ->fetchCol();
      if (!empty($ids)) {
        try {
          /**
           * @var \Drupal\danse\Entity\NotificationInterface[] $notifications
           */
          $notifications = $this->entityTypeManager->getStorage('danse_notification')
            ->loadMultiple($ids);
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          // @todo Log this exception.
        }
      }
    }
    return $notifications;
  }

  /**
   * Returns a list of user roles as a select list for forms.
   *
   * @return array
   *   List of user roles.
   */
  public function rolesAsSelectList(): array {
    static $roles;
    if (!isset($roles)) {
      $roles = [];
      try {
        foreach ($this->entityTypeManager->getStorage('user_role')
          ->loadMultiple() as $role_name => $role) {
          if ($role->id() !== 'anonymous') {
            $roles[$role_name] = $role->label();
          }
        }
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        // @todo Log this exception.
      }
    }
    return $roles;
  }

}
