<?php

declare(strict_types=1);

namespace Drupal\aws\Traits;

use Drupal\aws\AwsClientFactoryInterface;

/**
 * Common functionality for using the AWS client factory.
 */
trait AwsClientFactoryTrait {

  /**
   * The AWS client factory.
   *
   * @var \Drupal\aws\AwsClientFactoryInterface
   */
  protected $awsClientFactory;

  /**
   * Set the AWS client factory.
   *
   * @param \Drupal\aws\AwsClientFactoryInterface $client_factory
   *   The client factory.
   *
   * @return $this
   */
  protected function setAwsClientFactory(AwsClientFactoryInterface $client_factory) {
    $this->awsClientFactory = $client_factory;
    return $this;
  }

}
