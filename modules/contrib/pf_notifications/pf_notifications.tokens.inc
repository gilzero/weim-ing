<?php

/**
 * @file
 * Tokens definitions for Push notifications module.
 */

declare(strict_types=1);

/**
 * Implements hook_token_info_alter().
 */
function pf_notifications_token_info_alter(array &$data): void {

  if (isset($data['types']['push-object']) && isset($data['tokens']['push-object'])) {

    $data['tokens']['push-object']['topic'] = [
      'name' => t("Notification topic"),
      'description' => t("Sets DANSE's topic as a topic for the push notification."),
      'weight' => 0,
    ];
    $data['tokens']['push-object']['op'] = [
      'name' => t("Subscribed or unsubscribed"),
      'description' => t("Adds 'Subscribed to' or 'Unsubscribed from' or similar depending of the subscription operation."),
      'weight' => 1,
    ];
    $data['tokens']['push-object']['op_push'] = [
      'name' => t("Push or stop pushing"),
      'description' => t("Two verbs, 'push' or 'stop pushing'"),
      'weight' => 2,
    ];
    $data['tokens']['push-object']['client'] = [
      'name' => t("Subscription client"),
      'description' => t("A browser or a device of the current (un) subscribe action."),
      'weight' => 3,
    ];
    $data['tokens']['push-object']['clients'] = [
      'name' => t("Subscription clients"),
      'description' => t("A list of browsers or a devices which are a subscription client."),
      'weight' => 3,
    ];
    $data['tokens']['push-object']['parent_label'] = [
      'name' => t("Parent label"),
      'description' => t("A label of the parent entity (e.g. parent node label, if entity is a comment) of an entity that is a subject of subscription."),
      'weight' => -1,
    ];

    // Add some descriptions to existing Push framework's tokens for clarity.
    $data['tokens']['push-object']['content']['description'] = t('Rendered content, notification body as a string.');
    $data['tokens']['push-object']['label']['description'] = t('Label of an entity that is a subject of subscription.');
    $data['tokens']['push-object']['label']['weight'] = -2;
  }
}
