<?php

namespace Drupal\module_builder_devel\Form;

use Drupal\module_builder\Form\ProcessForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Replaces the code analysis form to add message about time taken.
 */
class ProcessFormExtra extends ProcessForm {

  /**
   * Implements callback_batch_operation().
   */
  public static function batchOperation($job_batch, &$context) {
    // Store the start time the first time this operation runs.
    if (!isset($context['results']['start_time'])) {
      $context['results']['start_time'] = \Drupal::time()->getRequestTime();
    }

    parent::batchOperation($job_batch, $context);
  }

  /**
   * Implements callback_batch_finished().
   */
  public static function batchFinished($success, $results, $operations) {
    parent::batchFinished($success, $results, $operations);

    $start_time = $results['start_time'];
    $end_time = \Drupal::time()->getRequestTime();
    $duration = $end_time - $start_time;

    \Drupal::messenger()->addStatus(t("Code analysis took @minutes minutes @seconds seconds.", [
      '@minutes' => floor($duration / 60),
      '@seconds' => $duration % 60,
    ]));
  }

}
