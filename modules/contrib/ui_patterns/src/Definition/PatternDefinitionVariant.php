<?php

namespace Drupal\ui_patterns\Definition;

/**
 * Definition class for a pattern variant.
 *
 * @package Drupal\ui_patterns\Definition
 */
class PatternDefinitionVariant implements \ArrayAccess {

  use ArrayAccessDefinitionTrait;

  /**
   * Default field values.
   *
   * @var array
   */
  protected $definition = [
    'name' => NULL,
    'label' => NULL,
    'description' => NULL,
    'use' => NULL,
  ];

  /**
   * PatternDefinitionVariant constructor.
   */
  public function __construct($name, $value) {
    if (is_scalar($value)) {
      $this->definition['name'] = is_numeric($name) ? $value : $name;
      $this->definition['label'] = $value;
    }
    else {
      $this->definition['name'] = $value['name'] ?? $name;
      $this->definition = $value + $this->definition;
    }
  }

  /**
   * Return array definition.
   *
   * @return array
   *   Array definition.
   */
  public function toArray() {
    return $this->definition;
  }

  /**
   * Get Name property.
   *
   * @return mixed
   *   Property value.
   */
  public function getName() {
    return $this->definition['name'];
  }

  /**
   * Get Label property.
   *
   * @return mixed
   *   Property value.
   */
  public function getLabel() {
    return $this->definition['label'];
  }

  /**
   * Get Description property.
   *
   * @return string
   *   Property value.
   */
  public function getDescription() {
    return $this->definition['description'];
  }

  /**
   * Set Description property.
   *
   * @param string $description
   *   Property value.
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->definition['description'] = $description;
    return $this;
  }

  /**
   * Getter.
   *
   * @return bool
   *   Whereas definition uses the "use:" property.
   */
  public function hasUse() {
    return !empty($this->definition['use']);
  }

  /**
   * Getter.
   *
   * @return string
   *   Property value.
   */
  public function getUse() {
    return $this->definition['use'];
  }

  /**
   * Setter.
   *
   * @param string $use
   *   Property value.
   *
   * @return $this
   */
  public function setUse($use) {
    $this->definition['use'] = $use;
    return $this;
  }

}
