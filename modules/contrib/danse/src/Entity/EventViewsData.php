<?php

namespace Drupal\danse\Entity;

use Drupal\views\EntityViewsData;

/**
 * Implements the entity view data for DANSE events.
 */
class EventViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();
    $data['danse_event']['label']['field']['id'] = 'danse_event_label';
    $data['danse_event']['reference']['field']['id'] = 'danse_event_reference';
    return $data;
  }

}
