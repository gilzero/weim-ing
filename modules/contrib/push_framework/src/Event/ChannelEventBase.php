<?php

namespace Drupal\push_framework\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\user\UserInterface;

/**
 * Abstract class for channel related events.
 */
abstract class ChannelEventBase extends Event {

  /**
   * The channel plugin.
   *
   * @var \Drupal\push_framework\ChannelPluginInterface
   */
  protected ChannelPluginInterface $channelPlugin;

  /**
   * The recipient of the notification.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * The content entity about which the notification will be sent.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * The display mode selected for the rendering.
   *
   * @var string
   */
  protected string $displayMode;

  /**
   * Base constructor for channel related events.
   *
   * @param \Drupal\push_framework\ChannelPluginInterface $channelPlugin
   *   The channel plugin.
   * @param \Drupal\user\UserInterface $user
   *   The recipient of the notification.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity about which the notification will be sent.
   * @param string $displayMode
   *   The display mode selected for the rendering.
   */
  public function __construct(ChannelPluginInterface $channelPlugin, UserInterface $user, ContentEntityInterface $entity, string &$displayMode) {
    $this->channelPlugin = $channelPlugin;
    $this->user = $user;
    $this->entity = $entity;
    $this->displayMode = &$displayMode;
  }

  /**
   * Get the channel plugin.
   *
   * @return \Drupal\push_framework\ChannelPluginInterface
   *   The channel plugin.
   */
  public function getChannelPlugin(): ChannelPluginInterface {
    return $this->channelPlugin;
  }

  /**
   * Get the recipient's user account.
   *
   * @return \Drupal\user\UserInterface
   *   The recipient's user account.
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

  /**
   * Get the entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity about which to push a notification.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Get the display mode.
   *
   * @return string
   *   The display mode.
   */
  public function getDisplayMode(): string {
    return $this->displayMode;
  }

}
