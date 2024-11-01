<?php

namespace Drupal\ui_patterns_settings\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Form;
use Drupal\ui_patterns\Definition\PatternDefinition;
use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\UiPatternsSettings;
use Drupal\ui_patterns_settings\UiPatternsSettingsManager;

/**
 * Build settings in manage display form.
 */
class SettingsFormBuilder {

  /**
   * Build a hidden tree link token for performance reasons.
   *
   * Forms with class .js-ui-patterns-settings-show-token-link will
   * generate Browse token button which click on the hidden link.
   * This will reduce the number of browse buttons.
   *
   * @param array $form
   *   The form.
   */
  private static function buildTokenLink(array &$form) {

    // Detecting current entity type.
    // We don't have access to
    $request = \Drupal::request();
    $entity_type_id = $request->attributes->get('entity_type_id');
    if (empty($entity_type_id)) {
      /** @var \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage $section_storage */
      $section_storage = $request->attributes->get('section_storage');
      if ($section_storage !== NULL) {
        $entity_type_id = explode('.', $section_storage->getStorageId())[0];
      }
    }

    if (!empty($entity_type_id)) {
      $content_entity_types[] = $entity_type_id;
    } else {
      $entity_type_definations = \Drupal::entityTypeManager()->getDefinitions();
      /** @var EntityTypeInterface $definition */
      foreach ($entity_type_definations as $definition) {
        if ($definition instanceof ContentEntityType) {
          $content_entity_types[] = $definition->id();
        }
      }
    }

    $form['token_link'] = [
      '#prefix' => '<div id="ui-patterns-settings-token-link">',
      '#suffix' => '</div>',
      '#theme' => 'token_tree_link',
      '#token_types' => $content_entity_types,
      '#show_restricted' => FALSE,
      '#show_nested' => FALSE,
      '#global_types' => TRUE,
      '#click_insert' => TRUE,
      '#recursion_limit' => 3,
      '#weight' => 90,
    ];
  }

  /**
   * Build pattern settings fieldset.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   * @param array $configuration
   *   The pattern configuration.
   */
  public static function layoutForm(array &$form, PatternDefinition $definition, array $configuration) {
    $settings = UiPatternsSettings::getPatternDefinitionSettings($definition);
    self::buildTokenLink($form);

    $form['#attached']['library'][] = 'ui_patterns_settings/widget';
    if (UiPatternsSettingsManager::allowVariantToken($definition)) {
      $variant_token_value = isset($configuration['pattern']['variant_token']) ? $configuration['pattern']['variant_token'] : NULL;
      $form['variant_token'] = [
        '#type' => 'textfield',
        '#title' => 'Variant token',
        '#attributes' => ['class' => ['js-ui-patterns-settings-show-token-link']],
        '#default_value' => $variant_token_value,
      ];
    }

    $form['variant']['#attributes']['class'][] = 'ui-patterns-variant-selector-' . $definition->id();
    if (!empty($settings)) {
      foreach ($settings as $key => $setting) {
        if (empty($setting->getType()) || !$setting->isFormVisible()) {
          continue;
        }

        if (!isset($form['settings'])) {
          $form['settings'] = [
            '#type' => 'fieldset',
            '#title' => t('Settings'),
          ];
        }
        $setting_value = $configuration['pattern']['settings'][$key] ?? NULL;
        $token_value = $configuration['pattern']['settings'][$key . "_token"] ?? "";
        $settingType = UiPatternsSettings::createSettingType($definition, $setting);
        $form['settings'] += $settingType->buildConfigurationForm([], $setting_value, $token_value, 'layouts_display');
      }
      SettingsFormBuilder::buildVariantsForm(".ui-patterns-variant-selector-" . $definition->id(), $form['settings'], $definition);
      SettingsFormBuilder::buildStatesForm(".ui-patterns-variant-selector-" . $definition->id(), $form['settings'], $definition);
    }
  }

  /**
   * Build pattern settings for display form.
   *
   * @param array $form
   *   Form array.
   * @param array $configuration
   *   Configurations array.
   */
  public static function displayForm(array &$form, array $configuration) {
    $form['#attached']['library'][] = 'ui_patterns_settings/widget';
    self::buildTokenLink($form);
    foreach (UiPatterns::getPatternDefinitions() as $pattern_id => $definition) {
      $settings = UiPatternsSettings::getPatternDefinitionSettings($definition);
      $form['variants'][$pattern_id]['#attributes']['class'][] = 'ui-patterns-variant-selector-' . $pattern_id;
      if (UiPatternsSettingsManager::allowVariantToken($definition)) {
        $variant_token_value = $configuration['variants_token'][$pattern_id] ?? NULL;
        $form['variants']['#weight'] = 20;
        $form['pattern_mapping']['#weight'] = 30;
        $form['pattern_settings']['#weight'] = 40;
        $form['variants_token'] = [
          '#type' => 'container',
          '#title' => t('Pattern Variant'),
          '#weight' => 25,
          '#states' => [
            'visible' => [
              'select[id="patterns-select"]' => ['value' => $pattern_id],
            ],
          ],
        ];
        $form['variants_token'][$pattern_id] = [
          '#type' => 'textfield',
          '#title' => t('Variant token'),
          '#default_value' => $variant_token_value,
          '#attributes' => ['class' => ['js-ui-patterns-settings-show-token-link']],
          '#states' => [
            'visible' => [
              'select[id="patterns-select"]' => ['value' => $pattern_id],
            ],
          ],
        ];
      }
      if (!empty($settings)) {
        foreach ($settings as $key => $setting) {
          if (empty($setting->getType()) || !$setting->isFormVisible()) {
            continue;
          }
          if (!isset($form['pattern_settings'][$pattern_id])) {
            $form['pattern_settings'][$pattern_id] = [
              '#type' => 'fieldset',
              '#title' => t('Settings'),
              '#states' => [
                'visible' => [
                  'select[id="patterns-select"]' => ['value' => $pattern_id],
                ],
              ],
            ];
          }
          $fieldset = &$form['pattern_settings'][$pattern_id];
          $settingType = UiPatternsSettings::createSettingType($definition, $setting);
          $setting_value = $configuration['pattern_settings'][$pattern_id][$key] ?? NULL;
          $token_value = $configuration['pattern_settings'][$pattern_id][$key . "_token"] ?? NULL;
          $fieldset += $settingType->buildConfigurationForm([], $setting_value, $token_value, 'display');
        }
        SettingsFormBuilder::buildVariantsForm('.ui-patterns-variant-selector-' . $pattern_id, $fieldset, $definition);
        SettingsFormBuilder::buildStatesForm('.ui-patterns-variant-selector-' . $pattern_id, $fieldset, $definition);
      }
    }
    if (!empty($form['pattern_settings'])) {
      $form['pattern_settings']['#element_validate'][] =
        [static::class, 'cleanSettings'];
    }
  }

  /**
   * Clean form values before being saved to configuration.
   *
   * @param array $element
   *   The pattern_settings fieldset element.
   * @param \Drupal\Core\Form\FormState $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   */
  public static function cleanSettings(array $element, FormState $form_state, array $form) {
    $values = $form_state->getValues();
    $pattern_config = NestedArray::getValue($values, array_slice($element['#parents'], 0, -1));
    if (!empty($pattern_config['pattern_settings'])) {
      // Only keep settings related to the selected pattern.
      $pattern_settings = array_intersect_key($pattern_config['pattern_settings'], [$pattern_config['pattern'] => TRUE]);
      $form_state->setValueForElement($element, $pattern_settings);
    }
  }

  /**
   * Build settings form.
   *
   * @param string $select_selector
   *   The id of the variant select field.
   * @param array $fieldset
   *   The fieldset.
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   */
  private static function buildStatesForm($variant_selector, array &$fieldset, PatternDefinition $definition) {
    $settings = UiPatternsSettings::getPatternDefinitionSettings($definition);
    /** @var PatternDefinitionSetting $setting */
    foreach ($settings as $setting_name => $setting) {
      $states = $setting->getStates();
      if (!is_null($states) && count($states) !== 0) {
        foreach ($states as $visibilty => $conditions) {
          foreach ($conditions as $state) {
            if (isset($state['variant'])) {
              $fieldset[$setting_name]['#states'][$visibilty][][$variant_selector]['value'] = $state['variant'];
              if (isset($fieldset[$setting_name . '_token'])) {
                $fieldset[$setting_name . '_token']['#states'][$visibilty][][$variant_selector]['value'] = $state['variant'];
              }
            }
            if (isset($state['setting'])) {
              $setting_select = '.js-ui-patterns-settings-' . $state['setting'];
              $fieldset[$setting_name]['#states'][$visibilty][][$setting_select]['value'] = $state['value'];
              if (isset($fieldset[$setting_name . '_token'])) {
                $fieldset[$setting_name . '_token']['#states'][$visibilty][][$setting_select]['value'] = $state['value'];
              }
            }
          }
        }
      }
    }
  }

   /**
   * Hide all settings which are configured by the variant.
   *
   * @param string $select_selector
   *   The id of the variant select field.
   * @param array $fieldset
   *   The fieldset.
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
   *   The pattern definition.
   */
  private static function buildVariantsForm($select_selector, array &$fieldset, PatternDefinition $definition) {
    $variants = $definition->getVariants();
    foreach ($variants as $variant) {
      $variant_ary = $variant->toArray();
      $settings = $variant_ary['settings'] ?? [];

      // Variants may have definition overwrites.
      // This should not lead to hide them. So unset them before.
      foreach ($settings as $name => $setting) {
        if (is_array($setting) && isset($setting['definition']) && empty($setting['value'])) {
          unset($settings[$name]);
        }
      }

      foreach ($settings as $name => $setting) {
        if (isset($fieldset[$name])) {
          // Add an or before a new state begins.
          if (isset($fieldset[$name]['#states']['invisible']) && count($fieldset[$name]['#states']['invisible']) != 0) {
            $fieldset[$name]['#states']['invisible'][] = 'or';
          }
          // Hide configured setting.
          $fieldset[$name]['#states']['invisible'][][$select_selector]['value'] = $variant->getName();
          if (isset($fieldset[$name . '_token'])) {
            $fieldset[$name . '_token']['#states']['invisible'][][$select_selector]['value'] = $variant->getName();
          }
        }
      }
    }
  }

}
