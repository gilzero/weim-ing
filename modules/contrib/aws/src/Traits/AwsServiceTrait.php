<?php

namespace Drupal\aws\Traits;

use Drupal\aws\Aws;

/**
 * Provides common AWS service functionality.
 */
trait AwsServiceTrait {

  /**
   * The AWS service.
   *
   * @var \Drupal\aws\Aws
   */
  protected $aws;

  /**
   * Set the AWS service.
   *
   * @param \Drupal\aws\Aws $aws
   *   The AWS service.
   *
   * @return $this
   */
  public function setAws(Aws $aws) {
    $this->aws = $aws;
    return $this;
  }

}
