<?php

namespace Drupal\danse_form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;

/**
 * The form payload class.
 *
 * @package Drupal\danse_form
 */
final class Payload extends PayloadBase {

  /**
   * The form.
   *
   * @var array
   */
  protected array $form;

  /**
   * The form payload constructor.
   *
   * @param array $form
   *   The form.
   */
  public function __construct(array $form) {
    $this->form = $form;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return $this->form['#form_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return implode('-', ['form', $this->form['#form_id']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    return [implode('-', ['form', $event->getTopic()])];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'form' => $this->form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    return new Payload($payload['form']);
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
      'plugin' => 'form',
      'topic' => '',
      'label' => '',
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
    return FALSE;
  }

}
