<?php

/**
 * @file
 * Preprocessors and theme functions of Ultimenu module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for ultimenu templates.
 *
 * Default template: ultimenu.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #config, #items, #plugin_id, #attributes.
 *     - config: The available config related to this particular instance.
 *     - items: An array of available items.
 *     - attributes: An associative array of attributes to be placed in the UL
 *       tag.
 *
 * @see ultimenu.html.twig
 */
function template_preprocess_ultimenu(array &$variables) {
  $element = $variables['element'];
  foreach (['config', 'items'] as $key) {
    $variables[$key] = $element["#$key"] ?? [];
  }

  $config = $variables['config'];
  $goodies = ultimenu()->getSetting('goodies');
  $name = $config['menu_name'];

  $variables['goodies'] = $goodies;
  $variables['attributes'] = new Attribute();
  $variables['attributes']['id'] = 'ultimenu-' . $name;
  $variables['attributes']['data-ultimenu'] = $name;

  if (!empty($config['ajaxify'])) {
    $variables['attributes']['data-ultiajax'] = TRUE;
  }

  $classes = $variables['attributes']['class'] ?? [];
  $main = $name == 'main' && empty($goodies['decouple-main-menu']);
  // @todo remove $main anytime later.
  if ($main || !empty($config['offcanvas'])) {
    $classes[] = 'ultimenu--offcanvas';
  }

  // States classes, gone at mobile devices.
  if (empty($config['hamburger'])) {
    $classes[] = 'is-ultihover';

    // Mobile has always carets, this is for desktop to repalce :hover by caret.
    if (!empty($config['caret'])) {
      $classes[] = 'is-ulticaret';
    }
  }

  if ($caret = $config['caret_skin'] ?? NULL) {
    $classes[] = 'is-ulticaret--' . $caret;
  }

  $variables['attributes']['class'] = array_merge(['ultimenu'], $classes);
}
