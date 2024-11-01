<?php

namespace Drupal\eca_danse\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\eca\Plugin\DataType\DataTransferObject;

/**
 * Provides the event that should select recipients for DANSE.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\danse\Event
 */
class RecipientSelection extends BaseEvent {

  /**
   * The data transfer object.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $recipients = NULL;

  /**
   * Init the event.
   */
  public function init(): void {
    if ($this->recipients === NULL) {
      $this->recipients = DataTransferObject::create();
      $this->recipients->setValue([]);
    }
  }

  /**
   * Gets the recipients as a list.
   *
   * @return \Drupal\eca\Plugin\DataType\DataTransferObject
   *   The recipients.
   */
  public function getRecipientsAsDto(): DataTransferObject {
    $this->init();
    return $this->recipients;
  }

  /**
   * Gets the recipients as a list.
   *
   * @return array
   *   The recipients.
   */
  public function getRecipientsAsList(): array {
    $this->init();
    $result = [];
    try {
      $items = $this->recipients->toArray();
    }
    catch (MissingDataException) {
      $items = [];
    }
    foreach ($items as $item) {
      if (is_scalar($item)) {
        $result[] = $item;
      }
      elseif ($item instanceof ContentEntityInterface) {
        $result[] = $item->id();
      }
      elseif ($item instanceof EntityAdapter && $entity = $item->getValue()) {
        $result[] = $entity->id();
      }
    }
    return $result;
  }

}
