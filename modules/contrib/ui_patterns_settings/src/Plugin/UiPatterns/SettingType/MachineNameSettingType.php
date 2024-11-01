<?php

namespace Drupal\ui_patterns_settings\Plugin\UiPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * MachineName setting type.
 *
 * @UiPatternsSettingType(
 *   id = "machine_name",
 *   label = @Translation("Machine name")
 * )
 */
class MachineNameSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $form[$def->getName()] = [
      '#type' => 'textfield',
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $this->getValue($value),
      '#pattern' => "[A-Za-z]+[\w\-]*",
    ];

    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

}
