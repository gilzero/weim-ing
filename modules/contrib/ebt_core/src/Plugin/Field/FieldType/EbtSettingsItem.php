<?php

namespace Drupal\ebt_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'settings' field type.
 *
 * @FieldType(
 *   id = "ebt_settings",
 *   label = @Translation("EBT Settings"),
 *   description = @Translation("This field stores EBT block settings in the database."),
 *   default_widget = "ebt_settings_default",
 *   default_formatter = "ebt_settings_default"
 * )
 */
class EbtSettingsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'ebt_settings' => [
          'description' => 'Serialized block settings data',
          'type' => 'blob',
          'serialize' => TRUE,
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('ebt_settings')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['ebt_settings'] = MapDataDefinition::create()
      ->setLabel(t('EBT settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // A map item has no main property.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values)) {
      $values += [
        'ebt_settings' => [],
      ];
    }

    parent::setValue($values, $notify);
  }

}
