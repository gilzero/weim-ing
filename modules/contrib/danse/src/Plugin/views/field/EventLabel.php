<?php

namespace Drupal\danse\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;

/**
 * Provides a field for views to show the label of a DANSE event.
 *
 * @ViewsField("danse_event_label")
 */
class EventLabel extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item): string { // phpcs:ignore
    /**
     * @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $raw
     */
    $raw = $item['raw'];
    /**
     * @var \Drupal\danse\Entity\Event $event
     */
    $event = $raw->getEntity();
    if ($payload = $event->getPayload()) {
      $label = $payload->label($event);
    }
    else {
      $label = 'unknown';
    }
    $item['rendered']['#context']['value'] = $label;
    return parent::render_item($count, $item);
  }

}
