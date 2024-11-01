<?php

namespace Drupal\danse;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\EventInterface;

/**
 * Interface for DANSE payload.
 */
interface PayloadInterface {

  /**
   * Get the label of the payload with the associated event.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The associated event.
   *
   * @return string
   *   The label.
   */
  public function label(EventInterface $event): string;

  /**
   * Get a unique string as reference to the associated event.
   *
   * @return string
   *   The unique reference.
   */
  public function getEventReference(): string;

  /**
   * Get all subscription references for the associated event.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The associated event.
   *
   * @return string[]
   *   The list of subscription references.
   */
  public function getSubscriptionReferences(EventInterface $event): array;

  /**
   * Prepare an array with all relevant properties of this payload.
   *
   * @return array
   *   The payload as an array.
   */
  public function prepareArray(): array;

  /**
   * Create a payload object from its array.
   *
   * @param array $payload
   *   The payload as an array.
   *
   * @return \Drupal\danse\PayloadInterface|null
   *   The payload object, if the array is valid, NULL otherwise.
   */
  public static function createFromArray(array $payload): ?PayloadInterface;

  /**
   * Get the associated content entity for the payload.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The associated content entity.
   */
  public function getEntity(): ContentEntityInterface;

  /**
   * Asserts if the given user ID has access to the associated entity.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE, if the user has access to the associated entity, FALSE otherwise.
   */
  public function hasAccess(int $uid): bool;

}
