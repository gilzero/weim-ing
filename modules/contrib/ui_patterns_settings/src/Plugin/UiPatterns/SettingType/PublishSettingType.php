<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Unpublish render array.
 *
 * @UiPatternsSettingType(
 *   id = "publish",
 *   label = @Translation("Publish ")
 * )
 */
class PublishSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    if ($this->isLayoutBuilderRoute() === FALSE && $value === FALSE) {
      hide($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    if ($value === NULL) {
      return $def->getDefaultValue();
    }
    return $value !== 0;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $form[$def->getName()] = [
      '#type' => 'checkbox',
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $this->getValue($value),
    ];
    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

}
