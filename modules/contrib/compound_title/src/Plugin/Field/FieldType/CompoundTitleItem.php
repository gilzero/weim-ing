<?php

namespace Drupal\compound_title\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'compound_title' field type.
 *
 * @FieldType(
 *   id = "compound_title",
 *   label = @Translation("Compound Title"),
 *   description = @Translation("Stores compound title field."),
 *   default_widget = "compound_title_default",
 *   default_formatter = "compound_title"
 * )
 */
class CompoundTitleItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['first_line'] = DataDefinition::create('string')
      ->setLabel(t('First line text'));

    $properties['second_line'] = DataDefinition::create('string')
      ->setLabel(t('Second line text'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'first_line' => [
          'description' => 'The first line text.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'second_line' => [
          'description' => 'The second line text.',
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }

    parent::setValue($values, $notify);
  }

}
