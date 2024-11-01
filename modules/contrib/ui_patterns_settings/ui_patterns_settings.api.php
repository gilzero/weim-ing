<?php

/**
 * @file
 * API file.
 */

/**
 * Alter UI Patterns Settings settings before they are printed.
 *
 * Implement this hook to override the configured pattern settings for
 * specific patterns or to configure custom setting logic.
 *
 * @param array $settings
 *   Pattern settings.
 * @param array $context
 *   Context Properties: The context and the entity of the pattern.
 *   keys:
 *    - #pattern_id: The pattern id.
 *    - #variant: The variant id.
 *    - #context: The pattern context
 *
 * @see \Drupal\ui_patterns_settings\Element\PatternSettings
 */
function hook_ui_pattern_settings_settings_alter(array &$settings, array $context) {
  if ($context['#pattern_id'] === 'button') {
    $settings['padding_bottom'] = 'large';
  }
}

/**
 * Alter UI Patterns variant before they are passed to settings.
 *
 * Implement this hook to override the configured pattern variant for
 * specific patterns or to configure custom variant logic.
 *
 * @param $variant
 *   Pattern variant.
 * @param array $context
 *   Context Properties: The context and the entity of the pattern.
 *   keys:
 *    - #pattern_id: The pattern id.
 *    - #variant: The variant id.
 *    - #context: The pattern context
 *
 * @see \Drupal\ui_patterns_settings\Element\PatternSettings
 */
function hook_ui_pattern_settings_variant_alter(&$variant, array $context) {
  if ($context['#pattern_id'] === 'section') {
    $variant = 'column_1';
  }
}
