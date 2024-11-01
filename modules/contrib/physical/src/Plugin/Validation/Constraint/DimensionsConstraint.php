<?php

namespace Drupal\physical\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Dimensions constraint.
 *
 * @Constraint(
 *   id = "Dimensions",
 *   label = @Translation("Dimension", context = "Validation"),
 *   type = { "physical_dimensions" }
 * )
 */
class DimensionsConstraint extends Constraint {

  /**
   * Empty Message variable.
   *
   * @var string
   */
  public $emptyMessage = '@name field is required.';

  /**
   * Invalid Message variable.
   *
   * @var string
   */
  public $invalidMessage = '@name field must be a number.';

}
