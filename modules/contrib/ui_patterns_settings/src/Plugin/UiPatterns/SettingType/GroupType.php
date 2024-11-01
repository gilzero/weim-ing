<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Group (fieldgroup/details) setting type.
 *
 * @UiPatternsSettingType(
 *   id = "group",
 *   label = @Translation("Group")
 * )
 */
class GroupType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $def = $this->getPatternSettingDefinition();
    $form[$def->getName()] = [
      '#type' => !empty($def->getValue('group_type')) ? $def->getValue('group_type') : 'fieldset',
      '#title' => $def->getLabel(),
    ];
    if (!empty($def->getDescription())) {
      $form[$def->getName()]['#description'] = $def->getDescription();
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    return NULL;
  }

}
