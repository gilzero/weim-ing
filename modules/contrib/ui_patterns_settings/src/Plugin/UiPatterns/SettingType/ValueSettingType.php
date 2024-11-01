<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Value setting type.
 *
 * @UiPatternsSettingType(
 *   id = "value",
 *   label = @Translation("Value")
 * )
 */
class ValueSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
          $value,
          $token_value,
          $form_type
  ) {
    return [];
  }

  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    return [];
  }

}
