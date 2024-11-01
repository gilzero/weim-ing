<?php

namespace Drupal\push_framework\Plugin\DanseRecipientSelection;

use Drupal\danse\PayloadInterface;
use Drupal\push_framework\SourcePluginInterface;

/**
 * Interface for DANSE recipient selection plugins with direct push.
 */
interface DirectPushInterface {

  /**
   * Pushes the payload directly.
   *
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   * @param \Drupal\push_framework\SourcePluginInterface|null $plugin
   *   The source plugin.
   * @param string|null $oid
   *   The id of the original object.
   *
   * @return string
   *   The constant from ChannelPluginInterface describing the result.
   */
  public function push(PayloadInterface $payload, SourcePluginInterface $plugin = NULL, string $oid = NULL): string;

  /**
   * Get the channel ID.
   *
   * @return string
   *   The channel ID.
   */
  public function directPushChannelId(): string;

}
