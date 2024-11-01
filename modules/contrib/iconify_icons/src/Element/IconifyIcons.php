<?php

namespace Drupal\iconify_icons\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

/**
 * Provides an entity autocomplete iconify icons form element.
 *
 * Properties:
 * - #collection: (optional) The ID of the target entity type.
 * - #settings: (optional) TRUE if the element allows multiple selection.
 * - #default_value: (optional) The preselected default value.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 * '#type' => 'iconify_icons',
 * '#collections' => 'collection_name',
 * '#settings' => [],
 * '#default_value' => 'icon_name (icon_collection)',
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 *
 * @FormElement("iconify_icons")
 */
class IconifyIcons extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = static::class;

    $info['#collections'] = '';
    $info['#settings'] = [];

    array_unshift($info['#process'], [$class, 'processIconifyIcons']);

    return $info;
  }

  /**
   * Adds entity autocomplete iconify icons functionality to a form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function processIconifyIcons(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element['#attached']['library'][] = 'iconify_icons/default';

    if (_iconify_icons_is_gin_theme_active()) {
      $element['#attached']['library'][] = 'iconify_icons/gin';
    }

    if (!isset($element['#attributes']['class'])) {
      $element['#attributes']['class'] = [];
    }

    $element['#attributes']['class'][] = 'iconify-icons-form-element';
    $element['#attributes']['class'][] = 'iconify-icons';
    $element['#description'] = t('Name of the Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
      '@iconsLink' => Link::fromTextAndUrl(t('the Iconify icon list'), Url::fromUri('https://icon-sets.iconify.design/', [
        'attributes' => [
          'target' => '_blank',
        ],
      ]))->toString(),
    ]);
    $element['#prefix'] = '<div class="iconify-icons-wrapper"><span class="iconify-icons-preview"></span>';
    $element['#suffix'] = '</div>';
    $element['#autocomplete_route_name'] = 'iconify_icons.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'collection' => $element['#collections'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#default_value']) {
      return $element['#default_value'];
    }

    return NULL;
  }

}
