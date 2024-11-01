<?php

namespace Drupal\eca_push_framework\Event;

/**
 * Defines events provided by the ECA Push Framework module.
 */
final class EcaPushFrameworkEvents {

  /**
   * Dispatches an ECA direct push event.
   *
   * @Event
   *
   * @var string
   */
  public const DIRECT_PUSH = 'eca_push_framework.direct_push';

}
