<?php

namespace Drupal\ui_patterns_settings\Definition;

use Drupal\ui_patterns\Definition\ArrayAccessDefinitionTrait;

/**
 * Pattern setting definition class.
 *
 * @package Drupal\ui_patterns_settings\Definition
 */
class PatternDefinitionSetting implements \ArrayAccess {

  use ArrayAccessDefinitionTrait;

  /**
   * Default setting values.
   *
   * @var array
   */
  protected $definition = [
    'name' => NULL,
    'label' => NULL,
    'description' => NULL,
    'type' => NULL,
    'required' => FALSE,
    'default_value' => NULL,
    'group' => NULL,
    'forced_value' => NULL,
    'options' => NULL,
    'form_visible' => TRUE,
    'allow_token' => FALSE,
    'expose_as_field' => FALSE,
  ];

  /**
   * PatternDefinitionSetting constructor.
   */
  public function __construct($name, $value) {
    if (is_scalar($value)) {
      $this->definition['name'] = is_numeric($name) ? $value : $name;
      $this->definition['label'] = $value;
      $this->definition['type'] = 'textfield';
      $this->definition['preview'] = NULL;
      $this->definition['group'] = NULL;
      $this->definition['weight'] = NULL;
      $this->definition['allow_token'] = FALSE;
      $this->definition['allow_expose'] = FALSE;
      $this->definition['expose_as_field'] = FALSE;
    }
    else {
      $name_key = !isset($value['name']) ? $name : $value['name'];
      $this->definition['name'] = $name_key;
      $this->definition['group'] = $value['group'] ?? NULL;
      $this->definition['weight'] = $value['weight'] ?? NULL;
      $this->definition['states'] = $value['states'] ?? [];
      $this->definition['type'] = $value['type'] ?? 'textfield';
      $this->definition['label'] = $value['label'] ?? $name_key;
      $this->definition['required'] = $value['required'] ?? FALSE;
      $this->definition['default_value'] = $value['default_value'] ?? NULL;
      $this->definition['preview'] = $value['preview'] ?? NULL;
      $unprocessed_options = $value['options'] ?? NULL;
      unset($value['options']);
      if (is_array($unprocessed_options)) {
        $processed_options = [];
        foreach ($unprocessed_options as $option_key => $option_value) {
          if (is_array($option_value)) {
            $processed_options[$option_key] = $option_value['label'] ?? 'undefined';
          }
          else {
            $processed_options[$option_key] = $option_value;
          }
        }
      } else {
        $processed_options = NULL;
      }
      $this->definition['options'] = $processed_options;

      $this->definition['allow_token'] = $value['allow_token'] ?? FALSE;
      $this->definition['allow_expose'] = $value['allow_expose'] ?? FALSE;
      $this->definition['expose_as_field'] = $value['expose_as_field'] ?? FALSE;

      $this->definition = $value + $this->definition;
    }
  }

  /**
   * Overwrite setting definition.
   *
   * @param $definitions
   *   The overwritten definitions.
   */
  public function setDefinitions($definitions) {
    $this->definition = $definitions + $this->definition;
  }

  /**
   * Return any definition value.
   *
   * @return string
   *   The value.
   */
  public function getValue($key) {
    return $this->definition[$key] ?? NULL;
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
   * Get Group property.
   *
   * @return mixed
   *   Property value.
   */
  public function getGroup() {
    return $this->definition['group'];
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
   * Get required property.
   *
   * @return mixed
   *   Property value.
   */
  public function getRequired() {
    return $this->definition['required'];
  }

  /**
   * Get allow token property.
   *
   * @return bool
   *   Property value.
   */
  public function getAllowToken() {
    return $this->definition['allow_token'];
  }

  /**
   * Get options array.
   *
   * @return mixed
   *   Property option.
   */
  public function getOptions() {
    return $this->definition['options'];
  }

  /**
   * Get default value property.
   *
   * @return mixed
   *   Property value.
   */
  public function getDefaultValue() {
    return $this->definition['default_value'];
  }

  /**
   * Set default value property.
   *
   * @return mixed
   *   Property value.
   */
  public function setDefaultValue($defaultValue) {
    $this->definition['default_value'] = $defaultValue;
    return $this;
  }

  /**
   * Get weight value property.
   *
   * @return mixed
   *   Property value.
   */
  public function getWeight() {
    return $this->definition['weight'];
  }

  /**
   * Set weight property.
   *
   * @return mixed
   *   Property value.
   */
  public function setWeight($weight) {
    $this->definition['weight'] = $weight;
    return $this;
  }

  /**
   * Set allow token value property.
   *
   * @param bool $allow_token
   *   Property value.
   *
   * @return $this
   */
  public function setAllowToken($allow_token) {
    $this->definition['allow_token'] = $allow_token;
    return $this;
  }

  /**
   * Get default value property.
   *
   * @return mixed
   *   Property value.
   */
  public function getForcedValue() {
    return $this->definition['forced_value'];
  }

  /**
   * Get preview property.
   *
   * @return mixed
   *   Property value.
   */
  public function getPreview() {
    return $this->definition['preview'];
  }

  /**
   * Set default value property.
   *
   * @return mixed
   *   Property value.
   */
  public function setForcedValue($forcedValue) {
    $this->definition['forced_value'] = $forcedValue;
    return $this;
  }

  /**
   * Get exposable property.
   *
   * @return string
   *   Property value.
   */
  public function allowExpose() {
    return $this->definition['allow_expose'];
  }

  /**
   * Set Exposable property.
   *
   * @param string $allow_expose
   *   Property value.
   *
   * @return $this
   */
  public function setAllowExpose($allow_expose) {
    $this->definition['allow_expose'] = $allow_expose;
    return $this;
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
   * Is form visible property.
   *
   * @return bool
   *   Property value.
   */
  public function isFormVisible() {
    return $this->definition['form_visible'];
  }

  /**
   * Set form visible property.
   *
   * @param bool $visible
   *   Property value.
   *
   * @return $this
   */
  public function setFormVisible($visible) {
    $this->definition['form_visible'] = $visible;
    return $this;
  }
  /**
   * Get Type property.
   *
   * @return string
   *   Property value.
   */
  public function getExposeAsField() {
    return $this->definition['expose_as_field'];
  }

  /**
   * Set Expose property.
   *
   * @param string $type
   *   Property value.
   *
   * @return $this
   */
  public function setExposeAsField($expose_as_field) {
    $this->definition['expose_as_field'] = $expose_as_field;
    return $this;
  }

  /**
   * Get states property.
   *
   * @return array
   *   Property value.
   */
  public function getStates() {
    return $this->definition['states'];
  }

  /**
   * Set States property.
   *
   * @param array $states
   *   Property value.
   *
   * @return $this
   */
  public function setStates($states) {
    $this->definition['states'] = $states;
    return $this;
  }

  /**
   * Get Type property.
   *
   * @return string
   *   Property value.
   */
  public function getType() {
    return $this->definition['type'];
  }

  /**
   * Set Type property.
   *
   * @param string $type
   *   Property value.
   *
   * @return $this
   */
  public function setType($type) {
    $this->definition['type'] = $type;
    return $this;
  }

}
