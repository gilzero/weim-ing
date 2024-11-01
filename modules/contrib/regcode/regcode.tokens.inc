<?php

/**
 * @file
 * Token support for the regcode module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

// cspell:ignore lastused regurl

/**
 * Implements hook_token_info().
 */
function regcode_token_info() {
  $type = [
    'name' => t('Registration Codes'),
    'description' => t('Replacement tokens for registration codes'),
    'needs-data' => 'regcode',
  ];

  $regcode['created'] = [
    'name' => t("Code creation time"),
    'description' => t("The date the regcode was created."),
    'type' => 'date',
  ];

  $regcode['lastused'] = [
    'name' => t("Code last used time"),
    'description' => t("The date the regcode was used last."),
    'type' => 'date',
  ];

  $regcode['begins'] = [
    'name' => t("Code activation date"),
    'description' => t("The date the regcode gets activated."),
    'type' => 'date',
  ];

  $regcode['expires'] = [
    'name' => t("Code expiry date"),
    'description' => t("The date the regcode expires."),
    'type' => 'date',
  ];

  $regcode['code'] = [
    'name' => t("Registration code"),
    'description' => t("One single registration code"),
  ];

  $regcode['regurl'] = [
    'name' => t("Registration URL"),
    'description' => t("Link to the registration page with regcode included"),
  ];

  $regcode['is_active'] = [
    'name' => t("State of the regcode"),
    'description' => t("Whether the code is active"),
  ];

  $regcode['maxuses'] = [
    'name' => t("Maximum usage count"),
    'description' => t("Maximum times a code can be used"),
  ];

  $regcode['uses'] = [
    'name' => t("Current usage count"),
    'description' => t("Number of times the code already has been used"),
  ];

  return [
    'types' => ['regcode' => $type],
    'tokens' => ['regcode' => $regcode],
  ];
}

/**
 * Implements hook_tokens().
 */
function regcode_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if (isset($options['language'])) {
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $replacements = [];

  if ($type == 'regcode' && !empty($data['regcode'])) {
    $regcode = $data['regcode'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($regcode->created, 'medium', '', NULL, $language_code);
          break;

        case 'lastused':
          $replacements[$original] = \Drupal::service('date.formatter')->format($regcode->created, 'medium', '', NULL, $language_code);
          break;

        case 'begins':
          $replacements[$original] = \Drupal::service('date.formatter')->format($regcode->created, 'medium', '', NULL, $language_code);
          break;

        case 'expires':
          $replacements[$original] = \Drupal::service('date.formatter')->format($regcode->created, 'medium', '', NULL, $language_code);
          break;

        case 'code':
          $replacements[$original] = $regcode->code;
          break;

        case 'regurl':
          $replacements[$original] = Url::fromRoute('user.register');
          break;

        case 'is_active':
          $replacements[$original] = $regcode->is_active ? t('active') : t('inactive');
          break;

        case 'maxuses':
          $replacements[$original] = $regcode->maxuses;
          break;

        case 'uses':
          $replacements[$original] = $regcode->uses;
          break;
      }
    }

    if ($created_tokens = \Drupal::token()->findWithPrefix($tokens, 'created')) {
      $replacements += \Drupal::token()->generate('date', $created_tokens, ['date' => $regcode->created], $options, $bubbleable_metadata);
    }

    if ($lastused_tokens = \Drupal::token()->findWithPrefix($tokens, 'lastused')) {
      $replacements += \Drupal::token()->generate('date', $lastused_tokens, ['date' => $regcode->lastused], $options, $bubbleable_metadata);
    }

    if ($begins_tokens = \Drupal::token()->findWithPrefix($tokens, 'begins')) {
      $replacements += \Drupal::token()->generate('date', $begins_tokens, ['date' => $regcode->begins], $options, $bubbleable_metadata);
    }

    if ($expires_tokens = \Drupal::token()->findWithPrefix($tokens, 'expires')) {
      $replacements += \Drupal::token()->generate('date', $expires_tokens, ['date' => $regcode->expires], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
