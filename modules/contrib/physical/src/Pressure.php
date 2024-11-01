<?php

namespace Drupal\physical;

/**
 * Provides a value object for pressure amounts.
 *
 * Usage example:
 * @code
 *   $pressure = new Pressure('150', PressureUnit::PSI);
 * @endcode
 */
final class Pressure extends Measurement {
  /**
   * The measurement type.
   *
   * @var string
   */
  protected $type = MeasurementType::PRESSURE;

}
