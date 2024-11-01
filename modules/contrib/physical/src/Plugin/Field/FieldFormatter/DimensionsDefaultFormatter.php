<?php

namespace Drupal\physical\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\physical\Length;
use Drupal\physical\LengthUnit;

/**
 * Plugin implementation of the 'physical_dimensions_default' formatter.
 *
 * @FieldFormatter(
 *   id = "physical_dimensions_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "physical_dimensions"
 *   }
 * )
 */
class DimensionsDefaultFormatter extends PhysicalFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\physical\UnitInterface $unit_class */
    $unit_class = $this->getUnitClass();
    $unit_labels = $unit_class::getLabels();

    $output_unit = $this->getSetting('output_unit');

    $element = [];
    $measurement_class = $this->getMeasurementClass();
    /** @var \Drupal\physical\Plugin\Field\FieldType\DimensionsItem $item */
    foreach ($items as $delta => $item) {
      $item_unit = $item->unit;
      $dimensions = [
        new $measurement_class($item->length, $item_unit),
        new $measurement_class($item->width, $item_unit),
        new $measurement_class($item->height, $item_unit),
      ];

      // If the output unit should be different from the input unit, convert
      // the input unit to the output unit:
      if (!empty($output_unit) && $output_unit !== $item_unit) {
        // Update the item unit, to be the output unit:
        $item_unit = $output_unit;

        foreach ($dimensions as &$dimension) {
          $dimension = $dimension->convert($output_unit);
        }
      }

      // Format the dimension values:
      $dimensions = array_map(fn($dimension) => $this->numberFormatter->format($dimension->getNumber()), $dimensions);

      $unit = $unit_labels[$item_unit] ?? $item_unit;

      $element[$delta] = [
        '#markup' => implode(' &times; ', $dimensions) . ' ' . $unit,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUnitClass() {
    return LengthUnit::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMeasurementClass() {
    return Length::class;
  }

}
