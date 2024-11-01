<?php

namespace Drupal\vertex_ai_search\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a configurable Vertex Search Filter plugin.
 */
interface VertexSearchFilterPluginInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Returns the formatted filter for the search request.
   *
   * @return string
   *   The string to be set as the filter in the Search Request.
   */
  public function getSearchFilter();

}
