<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Checkbox setting type.
 *
 * @UiPatternsSettingType(
 *   id = "boolean",
 *   label = @Translation("true/false")
 * )
 */
class BooleanSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $value = $this->getValue($value);
    $form[$def->getName()] = [
      '#type' => 'checkbox',
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $value,
    ];
    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExposableTypes() {
    return ['boolean'];
  }

}
