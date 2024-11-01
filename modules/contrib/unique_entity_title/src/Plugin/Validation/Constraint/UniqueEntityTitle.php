<?php

namespace Drupal\unique_entity_title\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "UniqueEntityTitle",
 *   label = @Translation("Unique Entity Title", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueEntityTitle extends Constraint {
  /**
   * The message that will be shown if the value is not unique.
   *
   * @var string
   */
  public $notUnique = '%label "%value" is already in use. It must be unique.';

}
