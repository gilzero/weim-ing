<?php

namespace Drupal\vertex_ai_search\Plugin;

/**
 * Provides an interface for a Vertex Search Results plugin.
 */
interface VertexSearchResultsPluginInterface {

  /**
   * Returns a modified array of search results.
   *
   * @param string $keyword
   *   String used for search query.
   * @param array $searchResults
   *   The search results of a search page.
   * @param string $search_page_id
   *   The id of the custom search page.
   *
   * @return array
   *   An array of manipulated results.
   */
  public function modifyPageResults(string $keyword, array $searchResults, string $search_page_id);

}
