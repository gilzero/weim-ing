<?php

namespace Drupal\physical\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\physical\MeasurementType;

/**
 * Plugin implementation of the 'physical_measurement_default' formatter.
 *
 * @FieldFormatter(
 *   id = "physical_measurement_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "physical_measurement"
 *   }
 * )
 */
class MeasurementDefaultFormatter extends PhysicalFormatterBase {

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
    /** @var \Drupal\physical\Plugin\Field\FieldType\MeasurementItem $item */
    foreach ($items as $delta => $item) {
      // Create new measurement object, from the number and unit:
      $measurement = new $measurement_class($item->number, $item->unit);

      // If the output unit should be different from the input unit, convert
      // the input unit to the output unit:
      if (!empty($output_unit) && $output_unit !== $item->unit) {
        $measurement = $measurement->convert($output_unit);
      }
      $number = $this->numberFormatter->format($measurement->getNumber());
      $unit = $unit_labels[$measurement->getUnit()] ?? $measurement->getUnit();

      $element[$delta] = [
        '#markup' => $number . ' ' . $unit,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUnitClass() {
    $measurement_type = $this->fieldDefinition->getSetting('measurement_type');
    return MeasurementType::getUnitClass($measurement_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMeasurementClass() {
    $measurement_type = $this->fieldDefinition->getSetting('measurement_type');
    return MeasurementType::getClass($measurement_type);
  }

}
