<?php

namespace Drupal\push_framework\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\user\UserInterface;

/**
 * Dispatched before a notification gets rendered in a specific language.
 */
class ChannelPreRender extends ChannelEventLanguageBase {

  /**
   * The built render array.
   *
   * @var array
   */
  protected array $elements;

  /**
   * {@inheritdoc}
   */
  public function __construct(ChannelPluginInterface $channelPlugin, UserInterface $user, ContentEntityInterface $entity, string $displayMode, string $languageKey, array &$elements) {
    parent::__construct($channelPlugin, $user, $entity, $displayMode, $languageKey);
    $this->elements = &$elements;
  }

  /**
   * Get the built render array.
   *
   * @return array
   *   The built render array.
   */
  public function getElements(): array {
    return $this->elements;
  }

  /**
   * Set the build render array.
   *
   * @param array $elements
   *   The built render array.
   *
   * @return ChannelPreRender
   *   This.
   */
  public function setElements(array $elements): ChannelPreRender {
    $this->elements = $elements;
    return $this;
  }

}
