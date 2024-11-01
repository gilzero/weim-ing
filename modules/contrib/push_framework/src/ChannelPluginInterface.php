<?php

namespace Drupal\push_framework;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Interface for push_framework plugins.
 */
interface ChannelPluginInterface extends PluginInspectionInterface {

  public const RESULT_STATUS_SUCCESS = 'success';
  public const RESULT_STATUS_RETRY = 'retry';
  public const RESULT_STATUS_FAILED = 'failed';

  /**
   * Returns the name of the config.
   *
   * @return string
   *   The name of the config.
   */
  public function getConfigName(): string;

  /**
   * Returns if this plugin is enabled.
   *
   * @return bool
   *   TRUE, if the channel plugin is active. FALSE otherwise.
   */
  public function isActive(): bool;

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Determines if the channel plugin is applicable for the given recipient.
   *
   * @param \Drupal\user\UserInterface $user
   *   The recipient's user account.
   *
   * @return bool
   *   TRUE, if this channel plugin is applicable, FALSE otherwise.
   */
  public function applicable(UserInterface $user): bool;

  /**
   * Sends the given notification.
   *
   * @param \Drupal\user\UserInterface $user
   *   The recipient's user account.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity about which to push a notification.
   * @param array $content
   *   The render array.
   * @param int $attempt
   *   The number of past attempts.
   *
   * @return string
   *   One fo the RESULT_STATUS_ constants above.
   */
  public function send(UserInterface $user, ContentEntityInterface $entity, array $content, int $attempt): string;

  /**
   * Prepares the content that should be pushed.
   *
   * @param \Drupal\user\UserInterface $user
   *   The recipient's user account.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for the notification.
   * @param \Drupal\push_framework\SourcePluginInterface|null $plugin
   *   The source plugin.
   * @param string|null $oid
   *   The ID of the original object.
   *
   * @return array
   *   A Drupal render array.
   */
  public function prepareContent(UserInterface $user, ContentEntityInterface $entity, SourcePluginInterface $plugin = NULL, string $oid = NULL): array;

}
