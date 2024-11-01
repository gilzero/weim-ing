<?php

namespace Drupal\vertex_ai_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * VertexSearchResults plugin manager.
 */
class VertexSearchResultsPluginManager extends DefaultPluginManager {

  /**
   * Constructs VertexSearchResultsPluginManager.
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
      'Plugin/SearchResults',
      $namespaces,
      $module_handler,
      'Drupal\vertex_ai_search\Plugin\VertexSearchResultsPluginInterface',
      'Drupal\vertex_ai_search\Annotation\VertexSearchResultsPlugin'
    );

    $this->setCacheBackend($cache_backend, 'vertex_search_results_plugins');
    $this->alterInfo('vertex_search_results_plugin');
  }

}
