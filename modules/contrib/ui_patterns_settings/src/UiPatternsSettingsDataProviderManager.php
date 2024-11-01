<?php

namespace Drupal\ui_patterns_settings;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * UiPatternsSettingsDataProvider plugin manager.
 */
class UiPatternsSettingsDataProviderManager extends DefaultPluginManager {

  /**
   * Constructs UiPatternsSettingsDataProviderPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/UiPatterns/SettingDataProvider',
      $namespaces,
      $module_handler,
      'Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderInterface',
      'Drupal\ui_patterns_settings\Annotation\UiPatternsSettingDataProvider'
    );
    $this->alterInfo('ui_patterns_settings_data_provider_info');
    $this->setCacheBackend($cache_backend, 'ui_patterns_settings_data_provider_plugins');
  }

  public function getDefinitionsBySettingType($setting_type_id) {
    $definitions = $this->getDefinitions();
    $results = [];
    foreach ($definitions as $definition) {
      if ($definition['settingType'] === $setting_type_id) {
        $results[] = $definition;
      }
    }
    return $results;
  }

}
