<?php

namespace Drupal\xray_audit\Services;

/**
 * Interface for services that retrieve the entities uses.
 *
 * @package Drupal\xray_audit\src\Services
 */
interface EntityUseInterface {

  /**
   * Init parameters.
   *
   * @param string $parent_entity_type
   *   Parent entity type.
   * @param string $entity_bundle
   *   Paragraph bundle to check.
   */
  public function initParameters(string $parent_entity_type, string $entity_bundle = NULL);

  /**
   * Get the uses count of the entity.
   *
   * @return array|null
   *   Count of use.
   */
  public function countEntityUses(): ?array;

  /**
   * Get the pages where the entity is used.
   *
   * @return array|null
   *   Where the entity it is used, generally the referenced is a node.
   */
  public function getEntityUsePlaces(): ?array;

}
