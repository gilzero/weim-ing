<?php

namespace Drupal\ui_patterns_settings;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * UI Patterns setting factory class.
 *
 * @package Drupal\ui_patterns_settings
 */
class ConfigManager {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * Storage array by type.
   *
   * @var array
   */
  private $typeMap = [];

  /**
   * Storage array by variant.
   *
   * @var array
   */
  private $variantMap = [];

  /**
   * Constructs a new Fast404ExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('ui_patterns_settings.settings');
  }

  /**
   * Add mapping.
   */
  public function addMapping($field_id, $pattern_setting_id) {
    $field_id = str_replace('.', '--', $field_id);
    $map = $this->config->get('mapping');
    if ($pattern_setting_id !== NULL) {
      $map[$field_id] = $pattern_setting_id;
    }
    else {
      unset($map[$field_id]);
    }

    $this->config->set('mapping', $map);
    $this->config->save();
  }

  /**
   * Find all variant mappings.
   *
   * @return string[]
   *   List of all variant mappings.
   */
  public function findVariantMappings($type) {
    if (isset($this->variantMap[$type])) {
      return $this->variantMap[$type];
    }
    $map = is_array($this->config->get('mapping')) ? $this->config->get('mapping') : [];
    foreach ($map as $field_id => $pattern_string) {
      [$pattern_id, $setting_id] = explode('::', $pattern_string);
      [$entity_type_id, $field_name] = explode('--', $field_id);
      if ($setting_id === 'variant') {
        $this->variantMap[$type][] = $field_name;
      }
    }
    if (!isset($this->variantMap[$type])) {
      $this->variantMap[$type] = [];
    }
    return $this->variantMap[$type];
  }

  /**
   * Gets all mapping by entity type.
   *
   * @return string[]
   *   List of mappings.
   */
  public function getMappingByType($type) {
    if (isset($this->typeMap[$type])) {
      return $this->typeMap[$type];
    }
    $map = is_array($this->config->get('mapping')) ? $this->config->get('mapping') : [];

    foreach ($map as $field_id => $pattern_id) {
      [$field_type, $field_name] = explode('--', $field_id);
      $this->typeMap[$field_type][$field_name] = $pattern_id;
    }
    if (!isset($this->typeMap[$type])) {
      $this->typeMap[$type] = [];
    }
    return $this->typeMap[$type];
  }

  /**
   * Get Mapping by field id.
   *
   * @return string
   *   The pattern.
   */
  public function getMapping($field_id) {
    $field_id = str_replace('.', '--', $field_id);
    $map = $this->config->get('mapping');
    return $map[$field_id] ?? NULL;
  }

}
