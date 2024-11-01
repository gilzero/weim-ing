<?php

namespace Drupal\advancedqueue_test\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;

/**
 * Avoid duplicates job type.
 *
 * @AdvancedQueueJobType(
 *   id = "avoid_duplicates",
 *   label = @Translation("Avoid duplicates"),
 *   allow_duplicates = false,
 * )
 */
class AvoidDuplicates extends JobTypeBase {

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    return JobResult::success();
  }

}
