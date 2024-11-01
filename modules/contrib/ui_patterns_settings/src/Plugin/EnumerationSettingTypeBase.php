<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;

/**
 * Base class for enumerations like radios or select.
 */
abstract class EnumerationSettingTypeBase extends PatternSettingTypeBase {

  /**
   * Returns empty option.
   *
   * @return array
   *   The empty option.
   */
  protected function emptyOption() {
    return ["" => $this->t("Please select")];
  }

  /**
   * Returns the enumeration type.
   *
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $def
   *   The pattern definition setting.
   *
   * @return string
   *   The enumeration type.
   */
  protected function getEnumerationType(PatternDefinitionSetting $def) {
    return $def->getValue('enumeration_type');
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldStorage(FieldStorageConfig $storage_config) {
    $storage_config->setSetting('allowed_values_function', 'ui_patterns_settings_allowed_values_function');
  }

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExposableTypes() {
    return ['list_string'];
  }

  /**
   * Returns the enumeration options.
   *
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $def
   *  The pattern definition.
   *
   * @return mixed
   *   The options.
   */
  protected function getOptions(PatternDefinitionSetting $def) {
    return $def->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    if ($def->getRequired() == FALSE) {
      $options = $this->emptyOption();
    }
    else {
      $options = [];
    }

    if ($this->getOptions($def) !== NULL) {
      $options += $this->getOptions($def);
    }
    $form[$def->getName()] = [
      '#type' => $this->getEnumerationType($def),
      '#title' => $def->getLabel(),
      '#description' => $def->getDescription(),
      '#default_value' => $this->getValue($value),
      '#options' => $options,
    ];
    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

}
