<?php

namespace Drupal\ui_patterns_settings;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ui_patterns\Definition\PatternDefinition;

/**
 * Provides the UI Patterns Settings plugin manager.
 */
class UiPatternsSettingsManager extends DefaultPluginManager implements PluginManagerInterface {

  use StringTranslationTrait;

  /**
   * UiPatternsSettingsManager constructor.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    parent::__construct('Plugin/UiPatterns/SettingType', $namespaces, $module_handler, 'Drupal\ui_patterns_settings\Plugin\PatternSettingTypeInterface', 'Drupal\ui_patterns_settings\Annotation\UiPatternsSettingType');
    $this->moduleHandler = $module_handler;
    $this->alterInfo('ui_patterns_settings_info');
    $this->setCacheBackend($cache_backend, 'ui_patterns_settings', ['ui_patterns_settings']);
  }

  /**
   * Returns TRUE if a variant token can configured.
   *
   * @param \Drupal\ui_patterns\Definition\PatternDefinition $pattern_definition
   *   The pattern definition.
   *
   * @return bool
   *   Returns TRUE if a variant token can configured.
   */
  public static function allowVariantToken(PatternDefinition $pattern_definition) {
    $ary = $pattern_definition->getAdditional();
    if (isset($ary['allow_variant_token']) && $ary['allow_variant_token'] === TRUE) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition);
    }
    return $plugin;
  }

}
