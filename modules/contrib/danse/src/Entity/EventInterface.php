<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginInterface;

/**
 * Interface for DANSE events.
 */
interface EventInterface extends ContentEntityInterface {

  /**
   * Gets the associated plugin ID.
   *
   * @return string
   *   The associated plugin ID.
   */
  public function getPluginId(): string;

  /**
   * Gets the associated plugin.
   *
   * @return \Drupal\danse\PluginInterface
   *   The associated plugin.
   */
  public function getPlugin(): PluginInterface;

  /**
   * Gets the associated payload.
   *
   * @return \Drupal\danse\PayloadInterface|null
   *   The associated payload, if still available. NULL otherwise.
   */
  public function getPayload(): ?PayloadInterface;

  /**
   * Gets the event topic.
   *
   * @return string
   *   The event topic.
   */
  public function getTopic(): string;

  /**
   * Determines, if user notifications should be pushed.
   *
   * @return bool
   *   TRUE, if notifications should be pushed, FALSE otherwise.
   */
  public function isPush(): bool;

  /**
   * Determines, if user notifications should be enforced, even if successors.
   *
   * @return bool
   *   TRUE, if the push notifications should be enforced.
   */
  public function isForce(): bool;

  /**
   * Sets the event as being processed.
   *
   * @return \Drupal\danse\Entity\EventInterface
   *   Self.
   */
  public function setProcessed(): EventInterface;

}
