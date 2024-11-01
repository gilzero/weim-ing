<?php

namespace Drupal\push_framework;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Interface for push_framework plugins.
 */
interface SourcePluginInterface extends PluginInspectionInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Get a list of all items that need to be pushed.
   *
   * @return \Drupal\push_framework\SourceItem[]
   *   The list of all items that need to be pushed.
   */
  public function getAllItemsForPush(): array;

  /**
   * Get the object of the item as an entity.
   *
   * @param string $oid
   *   The ID of the object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity derived from the object, or NULL if that's not available.
   */
  public function getObjectAsEntity(string $oid): ?ContentEntityInterface;

  /**
   * Confirms an attempt to push an object.
   *
   * @param string $oid
   *   The ID of the object.
   * @param \Drupal\user\UserInterface $user
   *   The recipient's user account.
   * @param \Drupal\push_framework\ChannelPluginInterface $channelPlugin
   *   The channel plugin which attempted to push the object.
   * @param string $result
   *   One of the ChannelPluginInterface::RESULT_STATUS_* strings.
   *
   * @return \Drupal\push_framework\SourcePluginInterface
   *   Self.
   */
  public function confirmAttempt(string $oid, UserInterface $user, ChannelPluginInterface $channelPlugin, string $result): SourcePluginInterface;

  /**
   * Confirms successful delivery of a pushed object.
   *
   * @param string $oid
   *   The ID of the object.
   * @param \Drupal\user\UserInterface $user
   *   The recipient's user account.
   *
   * @return \Drupal\push_framework\SourcePluginInterface
   *   Self.
   */
  public function confirmDelivery(string $oid, UserInterface $user): SourcePluginInterface;

}
