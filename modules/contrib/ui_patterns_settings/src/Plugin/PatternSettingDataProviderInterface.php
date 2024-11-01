<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;

/**
 * Interface for ui_patterns_settings_data_provider plugins.
 */
interface PatternSettingDataProviderInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Return the configuration form.
   *
   * @return array
   *   The configuration form.
   */
  public function settingsForm($value);

  /**
   * @return array
   *   The provided data.
   */
  public function getData($value);

  /**
   * Allow setting data providers to alter render element.
   *
   * @param string $value
   *   The value.
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $def
   *   The pattern definition.
   * @param array $element
   *   The render element.
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element);

}
