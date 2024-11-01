<?php

namespace Drupal\danse_config;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;

/**
 * The config payload class.
 *
 * @package Drupal\danse_config
 */
final class Payload extends PayloadBase {

  /**
   * The ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The data.
   *
   * @var array
   */
  protected array $data;

  /**
   * Config payload constructor.
   *
   * @param string $id
   *   The ID.
   * @param array $data
   *   The data.
   */
  public function __construct(string $id, array $data) {
    $this->id = $id;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return 'Config: ' . $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'id' => $this->id,
      'data' => $this->data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    return new Payload($payload['id'], $payload['data']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    /**
     * @var \Drupal\danse\Entity\Event $entity
     */
    $entity = Event::create([
      'id' => 0,
      'plugin' => 'config',
      'topic' => '',
      'label' => $this->id,
      'payload' => $this,
      'push' => TRUE,
      'force' => FALSE,
      'silent' => FALSE,
    ]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAccess(int $uid): bool {
    return TRUE;
  }

}
