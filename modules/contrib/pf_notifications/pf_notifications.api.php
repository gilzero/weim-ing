<?php

/**
 * @file
 * Hooks provided by the Push framework notifications module.
 */

declare(strict_types=1);

/**
 * @addtogroup hooks
 * @{
 */
use Drupal\comment\CommentInterface;
use Drupal\node\NodeInterface;

/**
 * Alter push data array, notifications settings and content.
 *
 * @param array<string, string|array<string, mixed>> $push_data
 *   Push data reference array.
 * @param \Drupal\comment\CommentInterface<object>|\Drupal\node\NodeInterface<object> $entity
 *   Child entity (e.g. comment) or main entity.
 * @param array<string, int|string> $entity_data
 *   Array collection with all data related to entity.
 *   Mostly useful for (any) parent entities to have available.
 */
function hook_pf_notifications_push_data(array &$push_data, CommentInterface|NodeInterface $entity, array $entity_data): void {
  // Change Notification title.
  $push_data['content']['title'] = t('Some other title');
  // Change Notification body.
  if ($entity->getEntityTypeId() === 'comment') {
    /** @var \Drupal\comment\CommentInterface<object> $entity */
    $push_data['content']['body'] = t('Some other body for message');
  }
  // Change Notification options.
  $push_data['options']['urgency'] = 'high';
}

/**
 * Specify own auth parameters.
 *
 * @param array<string, array<string>> $auth
 *   Array with auth parameters for WebPush constructor.
 *
 * @see https://github.com/web-push-libs/web-push-php#authentication-vapid
 */
function hook_pf_notifications_vapid(array &$auth): void {
  $auth['VAPID'] = [
    // Can be a mailto: or your website address.
    'subject' => 'mailto:me@website.com',
    // (Recommended) uncompressed public key P-256 encoded in Base64-URL.
    'publicKey' => '~88 chars',
    // (Recommended) in fact the secret multiplier of the private key,
    // encoded in Base64-URL.
    'privateKey' => '~44 chars',
    // If you have a PEM file and can link to it on your filesystem.
    'pemFile' => 'path/to/pem',
    // If you have a PEM file and want to hardcode its content.
    'pem' => 'pemFileContent',
  ];
}

/**
 * Alter subscribe/unsubscribe to notification content.
 *
 * @param array<string, string|array<string, mixed>> $data
 *   Array with subscription related data.
 */
function hook_pf_notifications_subscription_data(array &$data): void {
  $data['subscription_title'] = t('Welcome to our platform!');
  $data['subscription_body'] = t('This is the best way to stay up to date with our activities.');
  // Example - specify active theme's icon file.
  $theme_handler = \Drupal::service('theme_handler');
  $theme_path = $theme_handler->getTheme($theme_handler->getDefault())->getPath();
  $data['subscription_icon'] = \Drupal::service('file_url_generator')->generateAbsoluteString($theme_path . '/favicon.ico');
}

/**
 * Alter available tokens.
 *
 * Note, a part of, or say combined strings, it is resolved in front-end,
 * since web push subscription must happen there.
 *
 * @param array<string, string> $tokens
 *   Array with processed and ready tokens.
 * @param array<object> $context
 *
 *   Array containing entity objects and other params.
 *
 * @see \Drupal\pf_notifications\Service\Subscription::tokens()
 */
function hook_pf_notifications_tokens(array &$tokens, array $context): void {
  // Change label, that as a token may be used
  // in notification title or body.
  $tokens['label'] = t('A new label');
  // Do not use "push" or "stop pushing" [push-object:op_push] token.
  $tokens['op_push'] = FALSE;
}
