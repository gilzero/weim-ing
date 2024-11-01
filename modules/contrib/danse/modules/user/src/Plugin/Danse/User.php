<?php

namespace Drupal\danse_user\Plugin\Danse;

use Drupal\Core\Session\AccountInterface;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_user\UserPayload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "user",
 *   label = @Translation("User"),
 *   description = @Translation("Manages all user events.")
 * )
 */
class User extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof UserPayload;
  }

  /**
   * Creates a user event.
   *
   * @param string $topic
   *   The event topic.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The user event, if it was possible to create it, NULL otherwise.
   */
  public function createUserEvent(string $topic, AccountInterface $account): ?EventInterface {
    $message = $account->getDisplayName();
    $payload = new UserPayload($account);
    return $this->createEvent($topic, $message, $payload);
  }

  /**
   * Creates an event for changed roles.
   *
   * @param array $oldRoles
   *   The old roles.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function createChangedRolesEvents(array $oldRoles, AccountInterface $account): void {
    $payload = new UserPayload($account);
    $newRoles = $account->getRoles(TRUE);
    foreach (array_diff($oldRoles, $newRoles) as $role) {
      $this->createEvent(UserPayload::TOPIC_ROLE_REMOVED, $role, $payload);
    }
    foreach (array_diff($newRoles, $oldRoles) as $role) {
      $this->createEvent(UserPayload::TOPIC_ROLE_ADDED, $role, $payload);
    }
  }

}
