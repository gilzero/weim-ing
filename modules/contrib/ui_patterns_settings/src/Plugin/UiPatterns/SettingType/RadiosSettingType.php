<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\EnumerationSettingTypeBase;

/**
 * Radios setting type.
 *
 * @UiPatternsSettingType(
 *   id = "radios",
 *   label = @Translation("Radios")
 * )
 */
class RadiosSettingType extends EnumerationSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function getEnumerationType(PatternDefinitionSetting $def) {
    return 'radios';
  }

}
