<?php

namespace Drupal\push_framework\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\user\UserInterface;

/**
 * Abstract class for channel related events üer language.
 */
abstract class ChannelEventLanguageBase extends ChannelEventBase {

  /**
   * The language key used for building and rendering.
   *
   * @var string
   */
  protected string $languageKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(ChannelPluginInterface $channelPlugin, UserInterface $user, ContentEntityInterface $entity, string $displayMode, string $languageKey) {
    parent::__construct($channelPlugin, $user, $entity, $displayMode);
    $this->languageKey = $languageKey;
  }

  /**
   * Get the language key.
   *
   * @return string
   *   The language key.
   */
  public function getLanguageKey(): string {
    return $this->languageKey;
  }

}
