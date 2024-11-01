<?php

namespace Drupal\eca_danse\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 * Deriver for DANSE ECA event plugins.
 */
class DanseEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function definitions(): array {
    return DanseEvent::definitions();
  }

}
