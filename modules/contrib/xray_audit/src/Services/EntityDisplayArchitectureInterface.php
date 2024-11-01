<?php

namespace Drupal\xray_audit\Services;

/**
 * Retrieve data from entity about display modes.
 */
interface EntityDisplayArchitectureInterface {

  /**
   * Get the data ready to build the table render array.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $entity
   *   Entity.
   *
   * @return array
   *   Data.
   */
  public function getData(string $entity_type, string $entity) : array;

}
