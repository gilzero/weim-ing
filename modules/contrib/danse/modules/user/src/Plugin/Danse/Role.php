<?php

namespace Drupal\danse_user\Plugin\Danse;

use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_user\RolePayload;
use Drupal\user\RoleInterface;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "role",
 *   label = @Translation("Role"),
 *   description = @Translation("Manages all role events.")
 * )
 */
class Role extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof RolePayload;
  }

  /**
   * Creates an event for changed permissions.
   *
   * @param array $oldPermissions
   *   The old permissions.
   * @param \Drupal\user\RoleInterface $role
   *   The user role.
   */
  public function createChangedPermissionsEvents(array $oldPermissions, RoleInterface $role): void {
    $payload = new RolePayload($role);
    $newPermissions = $role->getPermissions();
    foreach (array_diff($oldPermissions, $newPermissions) as $permission) {
      $this->createEvent(RolePayload::TOPIC_PERMISSION_REMOVED, $permission, $payload);
    }
    foreach (array_diff($newPermissions, $oldPermissions) as $permission) {
      $this->createEvent(RolePayload::TOPIC_PERMISSION_ADDED, $permission, $payload);
    }
  }

}
