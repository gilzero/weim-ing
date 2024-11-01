<?php

namespace Drupal\eca_danse\Event;

/**
 * Defines events provided by the DANSE module.
 */
final class DanseEvents {

  /**
   * Dispatches a DANSE recipient selection by ECA event.
   *
   * @Event
   *
   * @var string
   */
  public const RECIPIENT_SELECTION = 'danse.recipient_selection';

}
