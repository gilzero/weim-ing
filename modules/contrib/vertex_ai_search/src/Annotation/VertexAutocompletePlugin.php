<?php

namespace Drupal\vertex_ai_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VertexAutocompletePlugin type annotation object.
 *
 * VertexAutocompletePlugin classes define autocomplete plugins for
 * vertex_ai_search module.
 *
 * @see VertexAutocompletePluginBase
 *
 * @ingroup vertex_ai_search
 *
 * @Annotation
 */
class VertexAutocompletePlugin extends Plugin {

  /**
   * A unique identifier for the vertex autocomplete plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The title for the vertex autocomplete plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @todo This will potentially be translated twice or cached with the wrong
   *   translation until the vertex autocomplete tabs are converted to
   *   local task plugins.
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
