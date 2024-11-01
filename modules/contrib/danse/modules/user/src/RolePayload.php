<?php

namespace Drupal\danse_user;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Class Payload.
 *
 * @package Drupal\danse_user
 */
final class RolePayload extends PayloadBase {

  public const TOPIC_PERMISSION_ADDED = 'permission_added';

  public const TOPIC_PERMISSION_REMOVED = 'permission_removed';

  /**
   * The user role.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected RoleInterface $role;

  /**
   * User role payload constructor.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The user role.
   */
  public function __construct(RoleInterface $role) {
    $this->role = $role;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return implode('/', [$this->role->label(), $event->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return implode('-', ['role', $this->role->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    return [implode('-', ['role', $event->getTopic()])];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'role' => $this->role->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    if ($role = Role::load($payload['role'])) {
      return new RolePayload($role);
    }
    return NULL;
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
      'plugin' => 'role',
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
