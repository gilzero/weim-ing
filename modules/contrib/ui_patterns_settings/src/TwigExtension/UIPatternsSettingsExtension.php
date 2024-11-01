<?php

namespace Drupal\ui_patterns_settings\TwigExtension;

use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns_settings\UiPatternsSettings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * UI Patterns Twig Extension.
 *
 * @package Drupal\ui_patterns_settings\TwigExtension
 */
class UIPatternsSettingsExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'ui_patterns_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction(
        'pattern_configuration',
        [$this, 'patternConfiguration']
      ),
    ];
  }

  /**
   * Returns pattern configuration.
   *
   * @param string $pattern_id
   *   The pattern id.
   * @param string $variant_id
   *   The variant id.
   * @param string $config_name
   *   The config name.
   *
   * @return mixed|null
   *   The pattern config
   */
  public function patternConfiguration($pattern_id, $variant_id, $config_name) {
    $definition = UiPatterns::getPatternDefinition($pattern_id);
    if ($definition !== NULL) {
      return UiPatternsSettings::getPatternConfiguration($definition, $variant_id, $config_name);
    }
  }

}
