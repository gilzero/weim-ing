<?php

namespace Drupal\push_framework;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\push_framework\Annotation\SourcePlugin;

/**
 * PushFramework plugin manager.
 */
class SourcePluginManager extends DefaultPluginManager {

  /**
   * Constructs PluginManager object.
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
      'Plugin/PushFrameworkSource',
      $namespaces,
      $module_handler,
      SourcePluginInterface::class,
      SourcePlugin::class
    );
    $this->alterInfo('push_framework_source_info');
    $this->setCacheBackend($cache_backend, 'push_framework_source_plugins', ['push_framework_source_plugins']);
  }

}
