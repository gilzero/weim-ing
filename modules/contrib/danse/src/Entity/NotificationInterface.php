<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for DANSE notification entities.
 */
interface NotificationInterface extends ContentEntityInterface {

  /**
   * Get the user ID for the notification.
   *
   * @return int
   *   The user ID.
   */
  public function uid(): int;

  /**
   * Get the related DANSE event for the notification.
   *
   * @return \Drupal\danse\Entity\EventInterface
   *   The DANSE event.
   */
  public function event(): EventInterface;

  /**
   * Marks the notification as seen.
   *
   * @return \Drupal\danse\Entity\NotificationInterface
   *   Self.
   */
  public function markSeen(): NotificationInterface;

  /**
   * Marks the notification as delivered.
   *
   * @return \Drupal\danse\Entity\NotificationInterface
   *   Self.
   */
  public function markDelivered(): NotificationInterface;

  /**
   * Sets a successor to the notification, i.e. a follow-up notification.
   *
   * @param \Drupal\danse\Entity\NotificationInterface $notification
   *   The succeeding notification.
   *
   * @return \Drupal\danse\Entity\NotificationInterface
   *   Self.
   */
  public function setSuccessor(NotificationInterface $notification): NotificationInterface;

}
