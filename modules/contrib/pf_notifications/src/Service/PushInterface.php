<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

/**
 * Web push notifications interface constructor.
 */
interface PushInterface {

  /**
   * Define default options for WebPush notification.
   *
   * @var array<string, int|string>
   */
  const DEFAULT_OPTIONS = [
    'ttl' => 2419200,
    'urgency' => 'normal',
    'topic' => 'default',
    'batch_size' => 1000,
  ];

  const DANSE_TOKENS = [
    'topic' => '[danse_notification:topic]',
  ];

  /**
   * Get default options for WebPush notification.
   *
   * @return array<string, int|string>
   *   Default options for the Link class.
   */
  public function defaultOptions(): array;

  /**
   * Check if notification Topic value is danse's token.
   *
   * @param string $value
   *   Topic string, most probably input value from config form.
   *
   * @return bool
   *   True if value is token.
   */
  public function isToken(string $value): bool;

  /**
   * Send the notification to all subscribers.
   *
   * @param array<string, string|array<string, mixed>> $push_data
   *   The data to push with options.
   * @param bool $test
   *   If it's a test notification found on config form.
   *
   * @throws \ErrorException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function sendNotification(array $push_data, bool $test = FALSE): void;

}
