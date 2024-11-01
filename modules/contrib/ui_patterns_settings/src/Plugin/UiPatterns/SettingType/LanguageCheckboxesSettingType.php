<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\EnumerationSettingTypeBase;
use Drupal\ui_patterns_settings\Plugin\LanguageCheckboxesSettingTypeBase;
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
class LanguageCheckboxesSettingType extends LanguageCheckboxesSettingTypeBase {

}
