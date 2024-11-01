<?php

namespace Drupal\iconify_icons\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides an iconify icon render element.
 *
 * @RenderElement("iconify_icon")
 */
class IconifyIcon extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = static::class;

    return [
      '#pre_render' => [
        [
          $class,
          'preRenderIconifyIcon',
        ],
      ],
      '#theme' => 'iconify_icon',
    ];
  }

  /**
   * Sets up the element for display.
   *
   * @param array $element
   *   An associative array with the attributes of the element.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderIconifyIcon(array $element): array {
    return $element;
  }

}
