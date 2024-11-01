<?php

namespace Drupal\ebt_core\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Generic Helper class to validate common fields.
 */
class EbtGenericValidator {

  /**
   * Validate if is the element has a valid class.
   */
  public static function validateClassElement($element, FormStateInterface $form_state, $form) {

    // If element is empty, skip.
    if (empty($element['#value'])) {
      return;
    }

    // Get the element value.
    $classes = explode(' ', $element['#value']);

    foreach ($classes as $class) {

      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $class)) {
        $errorMessage = (string) new TranslatableMarkup('Please insert a valid class');
        $form_state->setError($element, $errorMessage);
        return;
      }

    }

  }

}
