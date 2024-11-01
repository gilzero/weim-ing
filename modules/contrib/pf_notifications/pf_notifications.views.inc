<?php

/**
 * @file
 * Provide views data for pf_notifications.module.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function pf_notifications_views_data_alter(array &$data): void {
  if (isset($data['users_field_data'])) {
    $data['users_field_data']['pf_notifications'] = [
      'title' => t('Push subscriptions'),
      'help' => t('List Push notifications subscription data stored in "users_data" table'),
      'group' => t('Push notifications'),
      'field' => [
        'id' => 'pf_notifications_user_data_subscriptions',
        'multiple' => TRUE,
      ],
    ];
  }
}
