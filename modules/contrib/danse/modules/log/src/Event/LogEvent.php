<?php

namespace Drupal\danse_log\Event;

use Drupal\danse_log\Payload;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides an event to be triggered when somethings writes to the logs.
 *
 * @package Drupal\danse_log\Event
 */
class LogEvent extends Event {

  /**
   * The status.
   *
   * @var bool
   */
  protected bool $status = FALSE;

  /**
   * The log payload.
   *
   * @var \Drupal\danse_log\Payload
   */
  protected Payload $payload;

  /**
   * LogEvent constructor.
   *
   * @param \Drupal\danse_log\Payload $payload
   *   The log payload.
   */
  public function __construct(Payload $payload) {
    $this->payload = $payload;
  }

  /**
   * Marks this event relevant.
   */
  public function setRelevant(): self {
    $this->status = TRUE;
    return $this;
  }

  /**
   * Marks this event irrelevant.
   */
  public function setIrrelevant(): self {
    $this->status = FALSE;
    return $this;
  }

  /**
   * Determines if this log event is relevant for processing.
   *
   * @return bool
   *   TRUE, if it's relevant, FALSE otherwise.
   */
  public function isRelevant(): bool {
    return $this->status;
  }

  /**
   * Gets the log payload.
   *
   * @return \Drupal\danse_log\Payload
   *   The log payload.
   */
  public function getPayload(): Payload {
    return $this->payload;
  }

}
