<?php

namespace Drupal\danse\Plugin\DanseRecipientSelection;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\danse\PayloadInterface;
use Drupal\danse\RecipientSelectionBase;

/**
 * Plugin implementation of the DANSE role users recipient selection.
 *
 * @DanseRecipientSelection(
 *   id = "role",
 *   deriver = "Drupal\danse\Plugin\DanseRecipientSelection\RolesDeriver"
 * )
 */
class Roles extends RecipientSelectionBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(PayloadInterface $payload): array {
    $result = [];
    try {
      foreach ($this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('roles', $this->getPluginDefinition()['role'])
        ->execute() as $id) {
        $result[] = (int) $id;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
    return $result;
  }

}
