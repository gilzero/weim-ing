<?php

namespace Drupal\ui_patterns_settings\Plugin\UiPatterns\Source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ui_patterns\Plugin\PatternSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines UI Patterns Settings fields source plugin.
 *
 * @UiPatternsSource(
 *   id = "settings_field",
 *   label = @Translation("Seting Fields (UI Pattern settings)"),
 *   provider = "ui_patterns_settings",
 *   tags = {
 *     "field_properties"
 *   }
 * )
 */
class SettingFieldSource extends PatternSourceBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFields() {
    $sources = [];
    return $sources;
  }

}
