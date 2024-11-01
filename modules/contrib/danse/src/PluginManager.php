<?php

namespace Drupal\danse;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\danse\Annotation\Danse;

/**
 * DANSE plugin manager.
 */
class PluginManager extends DefaultPluginManager {

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
      'Plugin/Danse',
      $namespaces,
      $module_handler,
      PluginInterface::class,
      Danse::class
    );
    $this->alterInfo('danse_info');
    $this->setCacheBackend($cache_backend, 'danse_plugins', ['danse_plugins']);
  }

}
