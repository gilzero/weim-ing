<?php

/**
 * @file
 * Post update functions for Type Tray.
 */

use Drupal\node\Entity\NodeType;

/**
 * Enable Existing Nodes Link on existing installs.
 */
function type_tray_post_update_enable_existing_node_links(&$sandbox) {
  \Drupal::configFactory()
    ->getEditable('type_tray.settings')
    ->set('existing_nodes_link', TRUE)
    ->save(TRUE);
}

/**
 * Move Existing Nodes Link config to third-party settings.
 */
function type_tray_post_update_move_existing_node_links_to_type_settings(&$sandbox) {
  $config = \Drupal::configFactory()->getEditable('type_tray.settings');
  $existing_nodes_enabled = (bool) $config->get('existing_nodes_link');
  foreach (NodeType::loadMultiple() as $type) {
    $link_text = $existing_nodes_enabled ?
      t('View existing %type_label content', ['%type_label' => $type->label()]) :
      '';
    $type->setThirdPartySetting('type_tray', 'existing_nodes_link_text', $link_text);
    $type->save();
  }
  $config->clear('existing_nodes_link');
  $config->save(TRUE);
  return 'Successfully updated third-party settings link text on existing content types.';
}
