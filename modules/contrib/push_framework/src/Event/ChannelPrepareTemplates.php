<?php

namespace Drupal\push_framework\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\user\UserInterface;

/**
 * Dispatched when templates for a notification get prepared.
 */
class ChannelPrepareTemplates extends ChannelEventBase {

  /**
   * The subject template.
   *
   * @var string
   */
  protected string $subject;

  /**
   * The body template.
   *
   * @var string
   */
  protected string $body;

  /**
   * The text format for the body.
   *
   * @var string
   */
  protected string $textFormat;

  /**
   * Indicator if text format is html.
   *
   * @var bool
   */
  protected bool $isHtml;

  /**
   * {@inheritdoc}
   */
  public function __construct(ChannelPluginInterface $channelPlugin, UserInterface $user, ContentEntityInterface $entity, string &$displayMode, string &$subject, string &$body, string &$textFormat, bool &$isHtml) {
    parent::__construct($channelPlugin, $user, $entity, $displayMode);
    $this->subject = &$subject;
    $this->body = &$body;
    $this->textFormat = &$textFormat;
    $this->isHtml = &$isHtml;
  }

  /**
   * Get the subject template.
   *
   * @return string
   *   The subject template.
   */
  public function getSubject(): string {
    return $this->subject;
  }

  /**
   * Get the body template.
   *
   * @return string
   *   The body template.
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * Get the text format for the body.
   *
   * @return string
   *   The text format for the body.
   */
  public function getTextFormat(): string {
    return $this->textFormat;
  }

  /**
   * Get the indicator if the text format is html.
   *
   * @return bool
   *   TRUE, if the body text format is html, FALSE otherwise.
   */
  public function isHtml(): bool {
    return $this->isHtml;
  }

  /**
   * Set the display mode.
   *
   * @param string $displayMode
   *   The display mode.
   *
   * @return ChannelPrepareTemplates
   *   Self.
   */
  public function setDisplayMode(string $displayMode): ChannelPrepareTemplates {
    $this->displayMode = $displayMode;
    return $this;
  }

  /**
   * Set the subject template.
   *
   * @param string $subject
   *   The subject template.
   *
   * @return ChannelPrepareTemplates
   *   Self.
   */
  public function setSubject(string $subject): ChannelPrepareTemplates {
    $this->subject = $subject;
    return $this;
  }

  /**
   * Set the body template.
   *
   * @param string $body
   *   The body template.
   *
   * @return ChannelPrepareTemplates
   *   Self.
   */
  public function setBody(string $body): ChannelPrepareTemplates {
    $this->body = $body;
    return $this;
  }

  /**
   * Set the text format.
   *
   * @param string $textFormat
   *   The text format.
   *
   * @return ChannelPrepareTemplates
   *   Self.
   */
  public function setTextFormat(string $textFormat): ChannelPrepareTemplates {
    $this->textFormat = $textFormat;
    return $this;
  }

  /**
   * Set the indicator if the text format is html.
   *
   * @param bool $isHtml
   *   The indicator.
   *
   * @return ChannelPrepareTemplates
   *   Self.
   */
  public function setIsHtml(bool $isHtml): ChannelPrepareTemplates {
    $this->isHtml = $isHtml;
    return $this;
  }

}
