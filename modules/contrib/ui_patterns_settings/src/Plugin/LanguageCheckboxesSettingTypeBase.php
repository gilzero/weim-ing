<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Language Checkboxes setting type.
 *
 * Provides an array of:
 * - current_language_selected: True if the
 *   current language is part of the selection
 *   or nothing is selected
 * - current_language: The current language.
 * - selected: Array of selected languages.
 *
 * @UiPatternsSettingType(
 *   id = "language_checkboxes",
 *   label = @Translation("Language checkboxes")
 * )
 */
class LanguageCheckboxesSettingTypeBase extends EnumerationSettingTypeBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function emptyOption() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getValue($value) {
    if ($value === NULL) {
      return !is_array($this->getPatternSettingDefinition()
        ->getDefaultValue()) ? [
          $this->getPatternSettingDefinition()
            ->getDefaultValue(),
        ] : $this->getPatternSettingDefinition()->getDefaultValue();
    }
    else {
      return $value ?? "";
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(PatternDefinitionSetting $def) {
    $languages = $this->languageManager->getLanguages();
    $options = [];
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEnumerationType(PatternDefinitionSetting $def) {
    return $def->getValue('enumeration_type') ?? 'checkboxes';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    $selected_options = [];
    $defined_options = $this->getOptions($def);
    if (is_array($value)) {
      foreach ($value as $checkbox_key => $checkbox_value) {
        if ($checkbox_value != "0") {
          $selected_options[$checkbox_key] = $defined_options[$checkbox_value] ?? $checkbox_value;
        }
      }
    }
    $current_language = $this->languageManager->getCurrentLanguage();
    return [
      'current_language_selected' => count($selected_options) === 0 || isset($selected_options[$current_language->getId()]),
      'current_language' => [
        $current_language->getId() => $current_language->getName(),
      ],
      'selected' => $selected_options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
                       $plugin_id,
                       $plugin_definition
  ) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->languageManager = $container->get('language_manager');
    return $plugin;
  }

}
