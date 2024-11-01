<?php

namespace Drupal\raven\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event for altering Sentry client options.
 */
class OptionsAlter extends Event {

  /**
   * Create a new OptionsAlter event.
   *
   * @param mixed[] $options
   *   Sentry client options.
   */
  public function __construct(public array &$options) {
  }

}
