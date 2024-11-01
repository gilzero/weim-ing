<?php

namespace Drupal\vertex_ai_search\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a configurable Vertex Autocomplete plugin.
 */
interface VertexAutocompletePluginInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Grabs the suggestions for the autocompletion.
   *
   * @param string $keys
   *   The search keys.
   *
   * @return array
   *   An array of suggestions.
   */
  public function getSuggestions($keys);

}
