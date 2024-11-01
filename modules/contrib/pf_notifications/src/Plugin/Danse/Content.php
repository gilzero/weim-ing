<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Plugin\Danse;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\Entity\Notification;
use Drupal\danse_content\Plugin\Danse\Content as DanseContent;
use Drupal\user\Entity\User;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "pf_notifications_content",
 *   label = @Translation("Content"),
 *   description = @Translation("Manages all content entity types and their
 *   bundles.")
 * )
 */
class Content extends DanseContent {

  /**
   * Creates all required notifications for the given event.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The event.
   * @param string $trigger
   *   One of "push" or "subscription".
   * @param int $uid
   *   The user ID of the recipient.
   *
   * @return \Drupal\danse\Entity\NotificationInterface[]
   *   An array of already existing notifications, or an array containing one
   *   new notification, or an empty array, if the payload doesn't exist or the
   *   given user doesn't have access to the associated entity.
   */
  protected function createNotification(EventInterface $event, string $trigger, int $uid): array {
    $user = User::load($uid);

    if (!$user || !$user->isActive()) {
      // Do not create notifications for deleted or blocked users.
      return [];
    }
    $payload = $event->getPayload();
    if ($payload === NULL || !$payload->hasAccess($uid)) {
      return [];
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $payload->getEntity();
    $author = $entity->get('uid')->getValue()[0]['target_id'];
    // Do not create notification if user
    // who is doing an action is owner/author
    // of the entity on which action is done.
    if ($author == $uid) {
      return [];
    }

    $existingNotifications = $this->query->findSimilarEventNotifications($event, $uid);
    if (!empty($existingNotifications) && !$event->isForce()) {
      // There is an existing notification which is undelivered, so we do not
      // create a new one.
      return $existingNotifications;
    }

    /**
     * @var \Drupal\danse\Entity\NotificationInterface $notification
     */
    $notification = Notification::create([
      'event' => $event,
      'trigger' => $trigger,
      'uid' => $uid,
    ]);
    try {
      $notification->save();
    }
    catch (EntityStorageException $e) {
      // @todo Log the issue.
      return [];
    }
    // Mark existing notifications as redundant.
    foreach ($existingNotifications as $existingNotification) {
      $existingNotification->setSuccessor($notification);
    }
    return [$notification];
  }

}
