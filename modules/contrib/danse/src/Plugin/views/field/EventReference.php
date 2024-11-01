<?php

namespace Drupal\danse\Plugin\views\field;

use Drupal\Core\Access\AccessResult;
use Drupal\danse\Entity\Event;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Provides a field for views to show the reference of a DANSE event.
 *
 * @ViewsField("danse_event_reference")
 */
class EventReference extends EntityLink {

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $values) {
    $event = parent::getEntity($values);
    if ($event instanceof Event && $payload = $event->getPayload()) {
      return $payload->getEntity();
    }
    return $event;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkUrlAccess(ResultRow $row) {
    if (($entity = $this->getEntity($row)) && $entity->hasLinkTemplate($this->getEntityLinkTemplate())) {
      return parent::checkUrlAccess($row);
    }
    return AccessResult::forbidden();
  }

}
