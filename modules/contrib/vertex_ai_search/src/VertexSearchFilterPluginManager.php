<?php

namespace Drupal\vertex_ai_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * VertexSearchFilterPlugin plugin manager.
 */
class VertexSearchFilterPluginManager extends DefaultPluginManager {

  /**
   * Constructs VertexSearchFilterPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache, ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Plugin/SearchFilter',
      $namespaces,
      $moduleHandler,
      'Drupal\vertex_ai_search\Plugin\VertexSearchFilterPluginInterface',
      'Drupal\vertex_ai_search\Annotation\VertexSearchFilterPlugin'
    );

    $this->setCacheBackend($cache, 'vertex_search_filter_plugins');
    $this->alterInfo('vertex_search_filter_plugin');
  }

}
