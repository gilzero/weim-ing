<?php

namespace Drupal\danse\Plugin\PushFrameworkSource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\danse\Entity\Notification;
use Drupal\danse\Entity\NotificationAction;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\push_framework\SourceBase;
use Drupal\push_framework\SourceItem;
use Drupal\push_framework\SourcePluginInterface;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the push framework source.
 *
 * @SourcePlugin(
 *   id = "danse_notification",
 *   label = @Translation("DANSE Notification"),
 *   description = @Translation("Provides all the notifications that need to be pushed.")
 * )
 */
class DanseNotification extends SourceBase {

  /**
   * {@inheritdoc}
   */
  public function getAllItemsForPush(): array {
    $items = [];
    try {
      /**
       * @var \Drupal\danse\Entity\NotificationInterface[] $notifications
       */
      $notifications = $this->entityTypeManager->getStorage('danse_notification')
        ->loadByProperties([
          'delivered' => 0,
          'seen' => 0,
          'redundant' => 0,
        ]);
      foreach ($notifications as $notification) {
        $items[] = new SourceItem($this, $notification->id(), $notification->uid());
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectAsEntity($oid): ?ContentEntityInterface {
    if (($notification = Notification::load($oid)) && $payload = $notification->event()->getPayload()) {
      return $payload->getEntity();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function confirmAttempt($oid, UserInterface $user, ChannelPluginInterface $channelPlugin, $result): SourcePluginInterface {
    $action = NotificationAction::create([
      'notification' => $oid,
      'success' => $result === ChannelPluginInterface::RESULT_STATUS_SUCCESS,
      'payload' => [
        'channel plugin' => $channelPlugin->getPluginId(),
      ],
    ]);
    try {
      $action->save();
    }
    catch (EntityStorageException $e) {
      // @todo Log this exception.
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function confirmDelivery($oid, UserInterface $user): SourcePluginInterface {
    /**
     * @var \Drupal\danse\Entity\NotificationInterface $notification
     */
    $notification = Notification::load($oid);
    $notification->markDelivered();
    return $this;
  }

}
