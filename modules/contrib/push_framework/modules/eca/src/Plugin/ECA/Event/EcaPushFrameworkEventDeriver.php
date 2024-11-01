<?php

namespace Drupal\eca_push_framework\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 * Deriver for ECA Push Framework event plugins.
 */
class EcaPushFrameworkEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function definitions(): array {
    return EcaPushFrameworkEvent::definitions();
  }

}
