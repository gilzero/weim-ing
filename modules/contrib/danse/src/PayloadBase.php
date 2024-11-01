<?php

namespace Drupal\danse;

/**
 * Base class for DANSE plugins.
 */
abstract class PayloadBase implements PayloadInterface {

  /**
   * Create a payload object from a payload array.
   *
   * @param array $payload
   *   The payload array.
   *
   * @return PayloadInterface|null
   *   The payload object, if the payload array is valid, NULL otherwise.
   */
  final public static function fromArray(array $payload): ?PayloadInterface {
    $class = $payload['class'];
    if (!class_exists($class)) {
      return NULL;
    }
    unset($payload['class']);
    return $class::createFromArray($payload);
  }

  /**
   * Prepare an array with all relevant properties of this payload.
   *
   * @return array
   *   The payload as an array.
   */
  final public function toArray(): array {
    $payload = $this->prepareArray();
    $payload['class'] = get_class($this);
    return $payload;
  }

}
