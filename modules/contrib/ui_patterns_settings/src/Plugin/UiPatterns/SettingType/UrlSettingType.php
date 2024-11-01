<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Url;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\TokenSettingTypeBase;

/**
 * Url setting type.
 *
 * @UiPatternsSettingType(
 *   id = "url",
 *   label = @Translation("Url")
 * )
 */
class UrlSettingType extends TokenSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExposableTypes() {
    return ['link'];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessExposedField(FieldItemList $items) {
    foreach ($items as $item) {
      return $item->getUrl();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    $value = parent::settingsPreprocess($value, $context, $def);
    if (empty($value)) {
      return "";
    }
    try {
      $url = Url::fromUri($value)->toString();
    }
    catch (\Exception $e) {
      // Not a valid uri. Try user input:
      try {
        $url = Url::fromUserInput($value)->toString();
      }
      catch (\Exception $e) {
        // Not a valid url.
        return '#';
      }
    }
    return $url;
  }

}
