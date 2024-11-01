<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Textfield setting type.
 *
 * @UiPatternsSettingType(
 *   id = "textfield",
 *   label = @Translation("Textfield")
 * )
 */
class TextfieldSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $form[$def->getName()] = [
      '#type' => 'textfield',
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $this->getValue($value),
    ];

    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

}
