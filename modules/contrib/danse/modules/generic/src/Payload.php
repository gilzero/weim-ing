<?php

namespace Drupal\danse_generic;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;
use Drupal\user\Entity\User;

/**
 * The generic payload class.
 *
 * @package Drupal\danse_generic
 */
final class Payload extends PayloadBase {

  /**
   * The label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The arbitrary data object.
   *
   * @var mixed
   */
  protected mixed $data;

  /**
   * Webhook payload constructor.
   *
   * @param string $label
   *   The label.
   * @param mixed $data
   *   The arbitrary data object.
   */
  public function __construct(string $label, mixed $data) {
    $this->label = $label;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    if ($this->data instanceof ContentEntityInterface) {
      $id = [$this->data->getEntityTypeId(), $this->data->id()];
    }
    // @todo Add support for other data types.
    else {
      $id = [hash('md5', serialize($this->data))];
    }
    array_unshift($id, 'generic');
    return implode('-', $id);
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
      'label' => $this->label,
      'data' => $this->data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    return new Payload($payload['label'], $payload['data']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    if ($this->data instanceof ContentEntityInterface) {
      $entity = $this->data;
    }
    // @todo Add support for other data types.
    else {
      /**
       * @var \Drupal\danse\Entity\Event $entity
       */
      $entity = Event::create([
        'id' => 0,
        'plugin' => 'generic',
        'topic' => '',
        'label' => $this->label,
        'payload' => $this,
        'push' => TRUE,
        'force' => TRUE,
        'silent' => FALSE,
      ]);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAccess(int $uid): bool {
    if ($this->data instanceof ContentEntityInterface) {
      if ($account = User::load($uid)) {
        // @phpstan-ignore-next-line
        return $this->data->access('view', $account, TRUE);
      }
      return FALSE;
    }
    // @todo Add support for other data types.
    return TRUE;
  }

}
