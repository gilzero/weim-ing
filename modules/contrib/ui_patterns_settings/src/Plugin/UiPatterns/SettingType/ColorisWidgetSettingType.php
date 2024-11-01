<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\EnumerationSettingTypeBase;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Coloriswidget setting type.
 *
 * @UiPatternsSettingType(
 *   id = "coloriswidget",
 *   label = @Translation("Coloris Widget")
 * )
 */
class ColorisWidgetSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function getSettingTypeDependencies() {
    return ['coloris'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess(
    $value,
    array $context,
    PatternDefinitionSetting $def
  ) {
    if (is_string($value) && !empty($value)) {
      return $value;
    }
    return $value['coloris'] ?? '';
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $options = $def->getOptions() ?? [];
    $swatches = array_keys($options);
    $form[$def->getName()] = [
      '#type' => 'coloriswidget',
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $this->getValue($value),
      '#swatches' => $swatches
    ];

    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

}
