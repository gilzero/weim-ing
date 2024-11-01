<?php

namespace Drupal\eca_danse\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\danse\PayloadInterface;

/**
 * Provides an abstract base event.
 *
 * @package Drupal\danse\Event
 */
abstract class BaseEvent extends Event {

  /**
   * The ID for this event.
   *
   * @var string
   */
  protected string $eventId;

  /**
   * The payload.
   *
   * @var \Drupal\danse\PayloadInterface|null
   */
  protected ?PayloadInterface $payload;

  /**
   * Provides a custom event.
   *
   * @param string $event_id
   *   The ID for this event.
   * @param \Drupal\danse\PayloadInterface|null $payload
   *   The payload.
   */
  public function __construct(string $event_id, PayloadInterface $payload = NULL) {
    $this->eventId = $event_id;
    $this->payload = $payload;
  }

  /**
   * Gets the event ID.
   *
   * @return string
   *   The event ID.
   */
  public function getEventId(): string {
    return $this->eventId;
  }

}
