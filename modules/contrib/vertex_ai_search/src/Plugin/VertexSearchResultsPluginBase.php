<?php

namespace Drupal\vertex_ai_search\Plugin;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides base implementation for a Vertex Search Results plugin.
 */
abstract class VertexSearchResultsPluginBase extends PluginBase implements VertexSearchResultsPluginInterface, ContainerFactoryPluginInterface, RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function modifyPageResults(string $keyword, array $searchResults, string $search_page_id) {
    return $searchResults;
  }

}
