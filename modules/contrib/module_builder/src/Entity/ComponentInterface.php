<?php

namespace Drupal\module_builder\Entity;

/**
 * Interface for entities that represent a component to generate.
 */
interface ComponentInterface {

  /**
   * Gets the component type that should be passed to DCB.
   *
   * @return string
   *   The component type.
   */
  public function getComponentType(): string;

}