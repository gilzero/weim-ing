<?php

namespace Drupal\advancedqueue_test\Plugin\AdvancedQueue\Backend;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\Plugin\AdvancedQueue\Backend\BackendBase;

/**
 * A test backend that only extends BackendBase.
 *
 * By design it does not implement any additional interfaces.
 *
 * @AdvancedQueueBackend(
 *   id = "base_only",
 *   label = @Translation("Base only"),
 * )
 */
class BaseOnly extends BackendBase {

  /**
   * {@inheritdoc}
   */
  public function enqueueJob(Job $job, $delay = 0) {
    $this->enqueueJobs([$job], $delay);
  }

  /**
   * {@inheritdoc}
   */
  public function enqueueJobs(array $jobs, $delay = 0) {}

  /**
   * {@inheritdoc}
   */
  public function createQueue() {}

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {}

  /**
   * {@inheritdoc}
   */
  public function countJobs() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function retryJob(Job $job, $delay = 0) {}

  /**
   * {@inheritdoc}
   */
  public function claimJob() {}

  /**
   * {@inheritdoc}
   */
  public function onSuccess(Job $job) {}

  /**
   * {@inheritdoc}
   */
  public function onFailure(Job $job) {}

  /**
   * {@inheritdoc}
   */
  public function releaseJob($job_id) {}

  /**
   * {@inheritdoc}
   */
  public function deleteJob($job_id) {}

}
