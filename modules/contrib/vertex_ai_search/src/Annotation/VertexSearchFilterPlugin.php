<?php

namespace Drupal\vertex_ai_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VertexSearchFilterPlugin type annotation object.
 *
 * VertexSearchFilterPlugin classes define filter plugins for
 * vertex_ai_search module.
 *
 * @see VertexSearchFilterPluginBase
 *
 * @ingroup vertex_ai_search
 *
 * @Annotation
 */
class VertexSearchFilterPlugin extends Plugin {

  /**
   * A unique identifier for the vertex search filter plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The title for the vertex search filter plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
