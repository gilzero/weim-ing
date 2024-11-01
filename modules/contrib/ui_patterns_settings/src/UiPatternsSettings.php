<?php

namespace Drupal\ui_patterns_settings;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ui_patterns\Definition\PatternDefinition;
use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\Core\Entity\EntityMalformedException;

/**
 * UI Patterns setting factory class.
 *
 * @package Drupal\ui_patterns_settings
 */
class UiPatternsSettings {

  /**
   * Cached pattern definition settings.
   *
   * @var \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting[]
   */
  private static $settings;

  /**
   * Get pattern manager setting instance.
   *
   * @return \Drupal\ui_patterns_settings\UiPatternsSettingsManager
   *   UI Patterns setting manager instance.
   */
  public static function getManager() {
    return \Drupal::service('plugin.manager.ui_patterns_settings');
  }

  /**
   * Get config manager instance.
   *
   * @return \Drupal\ui_patterns_settings\ConfigManager
   *   UI Patterns setting config manager.
   */
  public static function getConfigManager() {
    return \Drupal::service('ui_patterns_settings.config_manager');
  }

  /**
   * Preprocess exposed settings variables.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity.
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   *
   * @return array
   *   The processed variables.
   */
  private static function preprocessExposedFields(ContentEntityBase $entity, PatternDefinition $definition) {
    $processed_settings = [];
    $mapping = self::getConfigManager()->getMappingByType($entity->getEntityTypeId());
    foreach ($mapping as $field => $pattern_setting) {
      if ($entity->hasField($field)) {
        [$pattern_id, $setting_id] = explode('::', $pattern_setting);
        if ($setting_id !== 'variant') {
          $pattern_definition = UiPatterns::getPatternDefinition($pattern_id);
          $setting_definition = UiPatternsSettings::getPatternDefinitionSetting($pattern_definition, $setting_id);
          if ($setting_definition !== NULL) {
            $settingType = UiPatternsSettings::createSettingType($definition, $setting_definition);
            $processed_settings[$setting_id] = $settingType->preprocessExposedField($entity->get($field));
          }
        }
      }
    }
    return $processed_settings;
  }

  /**
   * Preprocess all settings variables.
   *
   * @param string $pattern_id
   *   Pattern ID for which to preprocess.
   * @param array $settings
   *   The stored settings.
   * @param string $variant
   *   The variant.
   * @param bool $preview
   *   Is preview.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of the pattern. Useful for dynamic settings.
   *
   * @return array
   *   The processed settings.
   */
  public static function preprocess($pattern_id, array $settings, $variant, $preview, EntityInterface $entity = NULL, &$element = NULL) {
    $processed_settings = [];
    $definition = UiPatterns::getPatternDefinition($pattern_id);
    $context = [];
    $context['entity'] = $entity;
    if ($entity instanceof ContentEntityBase) {
      $processed_settings = self::preprocessExposedFields($entity, $definition);
    }
    $settings_definition = UiPatternsSettings::getPatternDefinitionSettings($definition);
    foreach ($settings_definition as $key => $setting_definition) {
      if ($setting_definition->getForcedValue()) {
        $value = $setting_definition->getForcedValue();
      }
      elseif (!empty($settings[$key . '_token'])) {
        $token_value = $settings[$key . '_token'];
        $token_data = [];
        if ($entity !== NULL) {
          $token_data[$entity->getEntityTypeId()] = $entity;
        }
        try {
          $value = \Drupal::token()
            ->replace($token_value, $token_data, ['clear' => TRUE]);
        }
        catch (EntityMalformedException $e) {
          if (!ui_patterns_settings_is_layout_builder_route()) {
            throw $e;
          }
          // Do nothing inside layout builder.
        }
      }
      // Use exposed field value if no other setting is provided
      elseif (isset($processed_settings[$key]) && empty($settings[$key])) {
        $value = $processed_settings[$key];
      }
      elseif (isset($settings[$key])) {
        $value = $settings[$key];
      }
      elseif ($preview && !empty($setting_definition->getPreview())) {
        $value = $setting_definition->getPreview();
      }
      else {
        $value = $setting_definition->getDefaultValue();
      }
      if (!empty($variant) && $definition->hasVariant($variant)) {
        $variant_ob = $definition->getVariant($variant);
        if ($variant_ob != NULL) {
          $variant_ary = $variant_ob->toArray();
          if (isset($variant_ary['settings']) && isset($variant_ary['settings'][$key])) {
            // Overwrite settings definition.
            // Allow variants to overwrite settings configuration.
            if (isset($variant_ary['settings'][$key]['definition']) && is_array($variant_ary['settings'][$key]['definition'])) {
              $value = $variant_ary['settings'][$key]['value'] ?? $value;
              if (isset($variant_ary['settings'][$key]['definition'])) {
                $setting_definition = clone $setting_definition;
                $setting_definition->setDefinitions($variant_ary['settings'][$key]['definition']);
              }
            } else {
              $value = $variant_ary['settings'][$key];
            }
          }
        }
      }
      $setting_type = UiPatternsSettings::createSettingType($definition, $setting_definition);
      $processed_value = $setting_type->preprocess($value, $context);
      $setting_type->alterElement($processed_value, $setting_definition, $element);
      $processed_settings[$key] = $processed_value;
    }
    return $processed_settings;

  }

  /**
   * Exposed pattern setting definitions.
   *
   * Returns a list of all exposed pattern setting definitions
   * filtered by the provided field storage type.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   * @param string $field_storage_type
   *   The field storage type.
   *
   * @return array
   *   The configuration array.
   *   Key: pattern::setting.
   *   Keys:
   *     'label' = 'The setting label'
   *     'definition' = The pattern definition
   */
  public static function getExposedPatternDefinition(PatternDefinition $definition, $field_storage_type) {
    $additional = $definition->getAdditional();
    $exposed = [];
    if (isset($additional['allow_variant_expose']) &&
      $additional['allow_variant_expose'] === TRUE && $field_storage_type === 'list_string') {
      $exposed[$definition->id() . '::variant'] = [
        'label' => $definition->getLabel() . ' Variants',
        'definition' => $definition,
      ];
    }

    $settings = self::getPatternDefinitionSettings($definition);
    /** @var \Drupal\ui_patterns\Definition\PatternDefinitionSetting $setting */
    foreach ($settings as $setting) {
      if ($setting->allowExpose()
      ) {
        $setting_type = self::createSettingType($definition, $setting);
        if (in_array($field_storage_type, $setting_type->fieldStorageExposableTypes())) {
          $exposed[$definition->id() . '::' . $setting->getName()] = [
            'label' => $definition->getLabel() . ' ' . $setting->getLabel(),
            'definition' => $definition,
          ];
        }
      }
    }
    return $exposed;
  }

  /**
   * Get pattern configuration for a pattern definition.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The definition.
   * @param string $variant
   *   The pattern variant.
   * @param string $name
   *   The configuration key.
   *
   * @return mixed
   *   Setting pattern configuration.
   */
  public static function getPatternConfiguration(PatternDefinition $definition, $variant = NULL, $name = NULL) {
    $additional = $definition->getAdditional();
    $configuration = $additional['configuration'] ?? [];
    // Check for variant configuration
    if (!empty($variant)) {
      $variant_ob = $definition->getVariant($variant);
      if ($variant_ob != NULL) {
        $variant_ary = $variant_ob->toArray();
        if (isset($variant_ary['configuration'])) {
          $configuration = array_merge($configuration, $variant_ary['configuration']);
        }
      }
    }
    // Check for settings configuration
    if (isset($additional['settings'][$name]['options'])) {
      foreach ($additional['settings'][$name]['options'] as $option_key => $option_value) {
        if (is_array($option_value) && isset($option_value['configuration'])) {
          $configuration[$name][$option_key] = $option_value['configuration'];
        }
      }
    }
    if ($name !== NULL && isset($configuration[$name])) {
      return $configuration[$name];
    }
    return $configuration;
  }

  /**
   * Get setting definition for a pattern and a setting name.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   * @param string $setting_name
   *   The setting name.
   */
  public static function getPatternDefinitionSetting(PatternDefinition $definition, $setting_name) {
    $definitions = self::getPatternDefinitionSettings($definition);
    return $definitions[$setting_name] ?? NULL;
  }

  /**
   * Get setting definitions for a pattern definition.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The definition.
   *
   * @return \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting[]
   *   Setting pattern definitons.
   */
  public static function getPatternDefinitionSettings(PatternDefinition $definition) {
    if (isset(self::$settings[$definition->id()])) {
      return self::$settings[$definition->id()];
    }
    $additional = $definition->getAdditional();
    $settings_ary = $additional['settings'] ?? [];
    $settings = [];
    if (!empty($settings_ary)) {
      foreach ($settings_ary as $key => $setting_ary) {
        $settings[$key] = new PatternDefinitionSetting($key, $setting_ary);
      }
    }
    self::$settings[$definition->id()] = $settings;
    return $settings;
  }

  /**
   * Create setting type plugin.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $pattern_definition
   *   The pattern definition.
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $setting_defintion
   *   The setting definition.
   *
   * @return \Drupal\ui_patterns_settings\Plugin\PatternSettingTypeInterface
   *   UI Patterns setting manager instance.
   */
  public static function createSettingType(PatternDefinition $pattern_definition, PatternDefinitionSetting $setting_defintion) {
    $configuration = [];
    $configuration['pattern_setting_definition'] = $setting_defintion;
    $configuration['pattern_definition'] = $pattern_definition;
    return \Drupal::service('plugin.manager.ui_patterns_settings')
      ->createInstance($setting_defintion->getType(), $configuration);
  }

}
