<?php

namespace Drupal\eca_push_framework\Plugin\PushFrameworkChannel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eca_push_framework\Event\DirectPush;
use Drupal\eca_push_framework\Event\EcaPushFrameworkEvents;
use Drupal\push_framework\ChannelBase;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the eca push framework channel.
 *
 * @ChannelPlugin(
 *   id = "eca",
 *   deriver = "Drupal\eca_push_framework\Plugin\PushFrameworkChannel\EcaDeriver"
 * )
 */
class Eca extends ChannelBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigName(): string {
    return 'eca_push_framework.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function applicable(UserInterface $user): bool {
    // This channel sends directly, so it never applies to other notifications.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function send(UserInterface $user, ContentEntityInterface $entity, array $content, int $attempt): string {
    $event = new DirectPush($this->getPluginDefinition()['event_id']);
    $this->eventDispatcher->dispatch($event, EcaPushFrameworkEvents::DIRECT_PUSH);
    return self::RESULT_STATUS_SUCCESS;
  }

}
