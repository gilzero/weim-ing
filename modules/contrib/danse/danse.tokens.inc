<?php

/**
 * @file
 * DANSE tokens file.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\danse\Entity\Notification;
use Drupal\danse\Plugin\PushFrameworkSource\DanseNotification;

/**
 * Implements hook_token_info().
 */
function danse_token_info(): array {
  $info = [];
  $info['tokens']['danse_notification']['topic'] = [
    'name' => t('Topic'),
    'description' => t('The topic of the notification, e.g. create/update/publish/etc. for content entity event notifications.'),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function danse_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type === 'danse_notification' && !empty($data['push_framework_source_plugin']) && !empty($data['push_framework_source_id'])) {
    $plugin = $data['push_framework_source_plugin'];
    $id = $data['push_framework_source_id'];
    if ($plugin instanceof DanseNotification && is_string($id) && $notification = Notification::load($id)) {
      foreach ($tokens as $name => $original) {
        if ($name === 'topic') {
          $replacements[$original] = $notification->event()->getTopic();
        }
      }
    }
  }

  return $replacements;
}
