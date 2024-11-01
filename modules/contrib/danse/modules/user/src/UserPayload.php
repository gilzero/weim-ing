<?php

namespace Drupal\danse_user;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;
use Drupal\user\Entity\User;

/**
 * Class Payload.
 *
 * @package Drupal\danse_user
 */
final class UserPayload extends PayloadBase {

  public const TOPIC_INSERT = 'insert';

  public const TOPIC_UPDATE = 'update';

  public const TOPIC_CANCEL = 'cancel';

  public const TOPIC_LOGIN = 'login';

  public const TOPIC_LOGOUT = 'logout';

  public const TOPIC_ROLE_ADDED = 'role_added';

  public const TOPIC_ROLE_REMOVED = 'role_removed';

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * User payload constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    $name = $this->account->getDisplayName();
    if (in_array($event->getTopic(), [
      self::TOPIC_ROLE_ADDED,
      self::TOPIC_ROLE_REMOVED,
    ], TRUE)) {
      return implode('/', [$name, $event->label()]);
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return implode('-', ['user', $this->account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    return [implode('-', ['user', $event->getTopic()])];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'account' => $this->account->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    if ($user = User::load($payload['account'])) {
      return new UserPayload($user);
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
      'plugin' => 'user',
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
