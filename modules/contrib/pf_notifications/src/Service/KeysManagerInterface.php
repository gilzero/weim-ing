<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

/**
 * Provides an interface to define a Keys manager.
 */
interface KeysManagerInterface {

  /**
   * Get key value.
   *
   * @param string $key
   *   Key name for the key.
   * @param string $value
   *   Value of the key.
   * @param bool $count
   *   Do count query if true.
   *
   * @return string|null
   *   The result of the database query.
   */
  public function getKey(string $key, string $value, bool $count = FALSE): string|NULL;

  /**
   * Set key.
   *
   * @param string $key
   *   Key name for the key.
   * @param string $value
   *   Value of the key.
   *
   * @return int|string|null
   *   The result of the database query.
   */
  public function setKey(string $key, string $value): int|string|NULL;

  /**
   * Delete key.
   *
   * @param string $key
   *   Key name for the key.
   *
   * @return int|null
   *   The result from the database query.
   */
  public function deleteKey(string $key): int|NULL;

}
