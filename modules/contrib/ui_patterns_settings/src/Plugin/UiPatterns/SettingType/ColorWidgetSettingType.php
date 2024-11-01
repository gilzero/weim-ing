<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\EnumerationSettingTypeBase;

/**
 * Colorwidget setting type.
 *
 * @UiPatternsSettingType(
 *   id = "colorwidget",
 *   label = @Translation("Color Widget")
 * )
 */
class ColorWidgetSettingType extends EnumerationSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function getSettingTypeDependencies() {
    return ['colorwidget'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEnumerationType(PatternDefinitionSetting $def) {
    return 'colorwidget';
  }

  /**
   * {@inheritdoc}
   */
  protected function emptyOption() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    return $value['colorwidget'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getValue($value) {
    if ($value === NULL) {
      return $this->getPatternSettingDefinition()->getDefaultValue();
    }
    else {
      return $value['colorwidget'] ?? $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function handleInput(array &$input, PatternDefinitionSetting $def, $form_type) {
    parent::handleInput($input, $def, $form_type);

    foreach ($input['#options'] as $key => $label) {
      $css_color = 'transparent';
      if (str_contains($label, '/')) {
        [$label, $css_color] = explode('/', $label);
      }
      $input['#colors'][$key] = [
        'label' => $label,
        'css_color' => $css_color,
      ];
    }
  }

}
