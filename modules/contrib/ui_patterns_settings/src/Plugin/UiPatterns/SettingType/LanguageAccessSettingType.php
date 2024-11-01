<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\LanguageCheckboxesSettingTypeBase;

/**
 * Hides render element for unchecked languages.
 *
 * Setting type to hide the render element if:
 * Elements are checked and the current language is
 * not part of the selection.
 *
 * @UiPatternsSettingType(
 *   id = "language_access",
 *   label = @Translation("Language Access")
 * )
 */
class LanguageAccessSettingType extends LanguageCheckboxesSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    if ($this->isLayoutBuilderRoute() === FALSE && $value['current_language_selected'] === FALSE) {
      hide($element);
    }
  }

}
