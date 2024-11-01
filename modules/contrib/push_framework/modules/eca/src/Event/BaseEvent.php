<?php

namespace Drupal\eca_push_framework\Event;

use Drupal\Component\EventDispatcher\Event;

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
   * Provides a custom event.
   *
   * @param string $event_id
   *   The ID for this event.
   */
  public function __construct(string $event_id) {
    $this->eventId = $event_id;
  }

  /**
   * Get event ID.
   *
   * @return string
   *   The event ID.
   */
  public function getEventId(): string {
    return $this->eventId;
  }

}
