<?php

namespace Drupal\eca_views\Event;

use Drupal\views\ViewExecutable;

/**
 * Provides an event when a view gets post render.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_views\Event
 */
class PostRender extends ViewsBase {

  /**
   * A structured content array representing the view output.
   *
   * @var array
   */
  protected array $output;

  /**
   * Constructs the ECA views event PostRender.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $output
   *   A structured content array representing the view output.
   */
  public function __construct(ViewExecutable $view, array &$output) {
    parent::__construct($view);
    $this->output = &$output;
  }

}
