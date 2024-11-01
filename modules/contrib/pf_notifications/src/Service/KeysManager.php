<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

use Drupal\Core\Database\Connection;

/**
 * Defines Keys manager object.
 */
final class KeysManager implements KeysManagerInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection service.
   */
  public function __construct(protected Connection $connection) {}

  /**
   * {@inheritDoc}
   */
  public function getKey(string $key, string $value, bool $count = FALSE): string|NULL {
    $query = $this->connection->select('pf_notifications', 'pfn');
    $query->addField('pfn', $value);
    $query->condition('key', $key, '=');
    return $count ? $query->countQuery()->execute()->fetchField() : $query->execute()->fetchField();
  }

  /**
   * {@inheritDoc}
   */
  public function setKey(string $key, string $value): int|string|null {
    $exists = $this->getKey($key, $value, TRUE);
    if ($exists) {
      $query = $this->connection->update('pf_notifications')
        ->fields(['value' => $value])
        ->condition('key', $key, '=')
        ->execute();
    }
    else {
      $query = $this->connection->insert('pf_notifications')
        ->fields(['key' => $key, 'value' => $value])
        ->execute();
    }
    return $query;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteKey(string $key): int|null {
    return $this->connection->delete('pf_notifications')->condition('key', $key)->execute();
  }

}
