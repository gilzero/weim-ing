<?php

namespace Drupal\drulma\Render;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a trusted callbacks to alter some elements markup.
 *
 * @see drulma_element_info_alter()
 */
class Callback implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderDetails',
      'preRenderRadios',
      'preRenderActions',
    ];
  }

  /**
   * Wrap all children with a panel block div.
   *
   * #pre_render callback.
   */
  public static function preRenderDetails($element) {
    foreach (Element::getVisibleChildren($element) as $child) {
      // Avoid nested panel blocks. This is the case for vertical_tabs.
      if (($element[$child]['#type'] ?? '') != 'details') {
        $element[$child]['#theme_wrappers'][] = 'container__panel_block';
      }
    }
    return $element;
  }

  /**
   * Correct theme wrappers set by preRenderDetails().
   *
   * The outer wrapper should always be the panel block container.
   * #pre_render callback.
   */
  public static function preRenderRadios($element) {
    $containerWrapperIndex = array_search('container__panel_block', $element['#theme_wrappers']);
    if ($containerWrapperIndex !== FALSE) {
      unset($element['#theme_wrappers'][$containerWrapperIndex]);
      $element['#theme_wrappers'][] = 'container__panel_block';
    }
    return $element;
  }

  /**
   * Add proper actions to sets of actions and its buttons.
   *
   * #pre_render callback.
   */
  public static function preRenderActions($element) {
    foreach (Element::getVisibleChildren($element) as $child) {
      $classes = $element[$child]['#attributes']['class'] ?? [];
      if (in_array('button--danger', $classes, TRUE)) {
        $element[$child]['#attributes']['class'][] = 'is-danger';
      }
      if (!empty($element[$child]['#button_type'])) {
        $element[$child]['#attributes']['class'][] = 'is-' . $element[$child]['#button_type'];
      }

    }
    $element['#attributes']['class'][] = 'control';
    $element['#attributes']['class'][] = 'buttons';
    return $element;
  }

}
