<?php

namespace Drupal\vertex_ai_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VertexSearchResultsPlugin type annotation object.
 *
 * VertexSearchResultsPlugin classes define plugins that can
 * manipulate a page of results on a Vertex AI Search page.
 *
 * @see VertexSearchResultsPluginBase
 *
 * @ingroup vertex_ai_search
 *
 * @Annotation
 */
class VertexSearchResultsPlugin extends Plugin {

  /**
   * A unique identifier for the vertex search results plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The title for the vertex search results plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Plugin weight.
   *
   * @var int
   */
  public $weight;

}
