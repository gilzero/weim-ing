<?php

/**
 * @file
 * Post update hooks for Ultimenu.
 */

/**
 * Contains breaking changes, see https://www.drupal.org/node/3447576.
 */
function ultimenu_post_update_breaking_changes_302() {
  // Do nothing to clear cache and info the changes.
}

/**
 * Remove unused themes key from ultimenu.settings.
 */
function ultimenu_post_update_remove_unused_themes_key_settings() {
  $config = \Drupal::configFactory()->getEditable('ultimenu.settings');
  $config->clear('themes');
  $config->save(TRUE);
}
