<?php

namespace Drupal\danse\Plugin\DanseRecipientSelection;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Deriver for DANSE recipient plugins for each user role.
 */
class RolesDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    foreach (Role::loadMultiple() as $id => $role) {
      if ($id === RoleInterface::ANONYMOUS_ID || $id === RoleInterface::AUTHENTICATED_ID) {
        continue;
      }
      $label = $role->label();
      $this->derivatives[$id] = [
        'role' => $id,
        'label' => $this->t('Role @label', ['@label' => $label]),
        'description' => t('Selects all active users with the %label role.', ['%label' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
