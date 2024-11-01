<?php

namespace Drupal\ui_patterns_settings\Element;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns_settings\UiPatternsSettings;
use Drupal\Core\Entity\EntityMalformedException;

/**
 * Renders a pattern element.
 */
class PatternSettings implements TrustedCallbackInterface {

  /**
   * Process settings for preview.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processPreviewSettings(array $element) {
    return PatternSettings::processSettings($element, TRUE);
  }

  /**
   * Process settings.
   *
   * @param array $element
   *   Render array.
   * @param bool $preview
   *   True when called in pattern preview mode.
   *
   * @return array
   *   Render array.
   */
  public static function processSettings(array $element, $preview = FALSE) {
    $alter_context = [];
    $context = $element['#context'];
    $pattern_id = $element['#id'];
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = $context->getProperty('entity');
    if ($context->getType() === 'layout' && $entity !== NULL) {
      $mappings = UiPatternsSettings::getConfigManager()->findVariantMappings($entity->getEntityTypeId());
      foreach ($mappings as $field_name) {
        if ($entity->hasField($field_name) && !empty($entity->get($field_name)->value)) {
          $element['#variant'] = $entity->get($field_name)->value;
        }
      }
    }

    // Handling variant token for layout builder.
    if (empty($element['#variant_token']) && isset($element['#layout'])) {
      /** @var \Drupal\ui_patterns_layout_builder\Plugin\Layout\PatternLayoutBuilder $layout */
      $layout = $element['#layout'];
      $configuration = $layout->getConfiguration();
      $element['#variant_token'] = $configuration['pattern']['variant_token'] ?? NULL;
    }

    // Handle Variant token.
    if (!empty($element['#variant_token'])) {
      $variant_token = $element['#variant_token'];
      $token_data = [];
      if ($entity !== NULL) {
        $token_data[$entity->getEntityTypeId()] = $entity;
      }
      try {
        $element['#variant'] = \Drupal::token()->replace($variant_token, $token_data, ['clear' => TRUE]);
      }
      catch (EntityMalformedException $e) {
        if (!ui_patterns_settings_is_layout_builder_route()) {
          throw $e;
        }
        // Do nothing inside layout builder.
      }
    }
    $variant_alter_context['#pattern_id'] = $pattern_id;
    if (isset($element['#layout'])) {
      $variant_alter_context['#layout'] = $element['#layout'];
    }
    $variant_alter_context['#pattern_context'] = $context;
    $variant = $element['#variant'];
    \Drupal::moduleHandler()->alter('ui_pattern_settings_variant', $variant, $variant_alter_context);
    $element['#variant'] = $variant;
    // Make sure we don't render anything in case fields are empty.
    if (self::hasSettings($element)) {
      $settings = $element['#settings'] ?? [];
      // Handling display suite pattern.
      if (empty($settings)) {
        $settings = $element['#ds_configuration']['layout']['settings']['pattern']['settings'] ?? [];
      }
      // Handling layout builder.
      if (empty($settings) && isset($element['#layout'])) {
        /** @var \Drupal\ui_patterns_layout_builder\Plugin\Layout\PatternLayoutBuilder $layout */
        $layout = $element['#layout'];
        $configuration = $layout->getConfiguration();
        $settings = $configuration['pattern']['settings'] ?? [];
      }
      $entity = $context->getProperty('entity');
      $variant = $element['#variant'] ?? NULL;
      $settings = UiPatternsSettings::preprocess($pattern_id, $settings, $variant, $preview, $entity, $element);
      if (isset($element['#layout'])) {
        $alter_context['#layout'] = $element['#layout'];
      }
      $alter_context['#pattern_id'] = $pattern_id;
      $alter_context['#variant'] = $variant;
      $alter_context['#pattern_context'] = $context;
      \Drupal::moduleHandler()->alter('ui_pattern_settings_settings', $settings, $alter_context);
      unset($element['#settings']);
      foreach ($settings as $name => $setting) {
        $key = '#' . $name;
        if (!isset($element[$key])) {
          $element[$key] = $setting;
        }
        else {
          if ($setting instanceof Attribute && $element[$key] instanceof Attribute) {
            $element[$key] = new Attribute(array_merge($setting->toArray(), $element[$key]->toArray()));
          }
          elseif (is_array($element[$key]) && is_array($setting)) {
            $element[$key] = array_merge($element[$key], $setting);
          } elseif (empty($element[$key]) && !empty($setting)) {
            // Handle Exposed fields. Overwrite expose fields if the setting is provided
            // but the variable prefilled with an empty field. (e.g. component blocks)
            $setting_definition = UiPatternsSettings::getPatternDefinitionSetting(UiPatterns::getPatternDefinition($pattern_id), $name);
            if ($setting_definition !== NULL && $setting_definition->getExposeAsField()) {
              $element[$key] = $setting;
            }
          }
        }
      }
    }
    return $element;
  }

  /**
   * Whereas pattern has settings or not.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public static function hasSettings($element) {
    $definition = UiPatterns::getPatternDefinition($element['#id']);
    $settings = UiPatternsSettings::getPatternDefinitionSettings($definition);
    if ($definition != NULL && count($settings) != 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processSettings', 'processPreviewSettings'];
  }

}
