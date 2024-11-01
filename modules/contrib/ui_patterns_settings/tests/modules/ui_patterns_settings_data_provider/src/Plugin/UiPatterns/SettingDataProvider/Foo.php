<?php

namespace Drupal\ui_patterns_settings_data_provider\Plugin\UiPatterns\SettingDataProvider;

use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderBase;
use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderInterface;

/**
 * Plugin implementation of the ui_patterns_settings_data_provider.
 *
 * @UiPatternsSettingDataProvider(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description."),
 *   settingType = "sample_complex_setting_type"
 * )
 */
class Foo extends PatternSettingDataProviderBase implements PatternSettingDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($value) {
    return ['#type' => 'select', '#options' => ['foo' => 'Foo']];
  }

  /**
   * {@inheritdoc}
   */
  public function getData($value) {
    return ['data' => 'done'];
  }

}
