<?php

namespace Drupal\iconify_icons\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Iconify Icon.
 *
 * @FieldType(
 *   id = "iconify_icon",
 *   label = @Translation("Iconify Icon"),
 *   category = "reference",
 *   description = @Translation("An entity field containing an entity reference."),
 *   default_formatter = "iconify_icon_formatter",
 *   default_widget = "iconify_icon_widget",
 *   serialized_property_names = {
 *     "settings"
 *   }
 * )
 */
class IconifyIcon extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        'icon' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        'settings' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['icon'] = DataDefinition::create('string')
      ->setLabel(t('Icon'))
      ->setDescription(t('The full name of the icon'));
    $properties['settings'] = DataDefinition::create('string')
      ->setLabel(t('Icon Settings'))
      ->setDescription(t('The additional class settings for the icon'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $icon_name = $this->get('icon')->getValue();
    return $icon_name === NULL || $icon_name === '';
  }

}
