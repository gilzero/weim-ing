<?php

namespace Drupal\schemata_json_schema\Normalizer\jsonapi;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Normalizer for FieldDefinitionInterface objects.
 *
 * This normalizes the variant of data fields particular to the Field system.
 * By accessing this via the FieldDefinitionInterface, there is greater access
 * to some of the methods providing deeper schema properties.
 */
class FieldDefinitionNormalizer extends ListDataDefinitionNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldDefinitionInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_definition, $format = NULL, array $context = []): array|bool|string|int|float|null|\ArrayObject {
    assert($field_definition instanceof FieldDefinitionInterface);
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $context['cardinality'] = $cardinality;
    $normalized = parent::normalize($field_definition, $format, $context);

    // Specify non-contextual default value as an example.
    $default_value = $field_definition->getDefaultValueLiteral();
    $field_name = $context['name'];
    if (!empty($default_value)) {
      $field_type = $field_definition->getType();
      $default_value = $cardinality == 1 ? reset($default_value) : $default_value;
      $default_value = count($default_value) == 1 ? reset($default_value) : $default_value;
      $default_value = $field_type == "boolean" ? boolval($default_value) : $default_value;
      NestedArray::setValue(
        $normalized,
        ['properties', 'attributes', 'properties', $field_name, 'default'],
        $default_value
      );
    }

    // The cardinality is the configured maximum number of values the field can
    // contain. If unlimited, we do not include a maxItems attribute.
    if ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $cardinality != 1) {
      NestedArray::setValue(
        $normalized,
        ['properties', 'attributes', 'properties', $field_name, 'maxItems'],
        $cardinality
      );
    }

    // Allow null values if the field is not required.
    if (!$field_definition->isRequired()) {
      NestedArray::setValue(
        $normalized,
        ['properties', 'attributes', 'properties', $field_name, 'x-nullable'],
        TRUE
      );
    }

    return $normalized;
  }

  /**
   *{@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      FieldDefinitionInterface::class => true,
    ];
  }

}
