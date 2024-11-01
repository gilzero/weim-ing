<?php

namespace Drupal\push_framework\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\user\UserInterface;

/**
 * Dispatched after a notification got rendered in a specific language.
 */
class ChannelPostRender extends ChannelEventLanguageBase {

  /**
   * The rendered notification message.
   *
   * @var string
   */
  protected string $output;

  /**
   * {@inheritdoc}
   */
  public function __construct(ChannelPluginInterface $channelPlugin, UserInterface $user, ContentEntityInterface $entity, string $displayMode, string $languageKey, string &$output) {
    parent::__construct($channelPlugin, $user, $entity, $displayMode, $languageKey);
    $this->output = &$output;
  }

  /**
   * Get the rendered notification message.
   *
   * @return string
   *   The rendered notification message.
   */
  public function getOutput(): string {
    return $this->output;
  }

  /**
   * Set the rendered notification message.
   *
   * @param string $output
   *   The rendered notification message.
   *
   * @return ChannelPostRender
   *   This.
   */
  public function setOutput(string $output): ChannelPostRender {
    $this->output = $output;
    return $this;
  }

}
