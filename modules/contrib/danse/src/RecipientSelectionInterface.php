<?php

namespace Drupal\danse;

/**
 * Interface for DANSE recipient selection plugins.
 */
interface RecipientSelectionInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Returns a list of user IDs who should be notified about the payload.
   *
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   *
   * @return int[]
   *   List of user IDs.
   */
  public function getRecipients(PayloadInterface $payload): array;

}
