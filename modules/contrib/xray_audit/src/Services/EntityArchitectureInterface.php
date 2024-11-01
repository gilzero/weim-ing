<?php

namespace Drupal\xray_audit\Services;

/**
 * Retrieve data about Entity Field Architecture.
 */
interface EntityArchitectureInterface {

  const TYPE_BASE_FIELD_DEFINITION = 'base_field';

  const TYPE_BASE_FIELD_OVERRIDE = 'base_field_override';

  const TYPE_BASE_FIELD_CONFIG = 'field_config';

  /**
   * Get info of all entity fields.
   *
   * @return array
   *   Info of all entity fields.
   */
  public function getDataForEntityFieldArchitecture();

  /**
   * Get data needed for View display Architecture.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $bundle
   *   Bundle.
   *
   * @return array
   *   Data.
   */
  public function getDataForViewDisplayArchitecture(string $entity_type_id, string $bundle): array;

  /**
   * Get data about Entity Field Architecture.
   *
   * @param string $entity_type_id
   *   Entity type name. For example, node_type.
   * @param string $bundle
   *   Entity name. For example, node.
   *
   * @return array
   *   Data in an array.
   */
  public function getEntityFieldData(string $entity_type_id, string $bundle);

  /**
   * Get content entities definitions.
   *
   * @return array
   *   Entities definition.
   */
  public function getContentEntitiesInfo(): array;

}
