<?php

declare(strict_types=1);

namespace Drupal\aws;

use Drupal\aws\Entity\ProfileInterface;
use Drupal\aws\Traits\AwsServiceTrait;

/**
 * Factory class for AWS client instances.
 */
class AwsClientFactory implements AwsClientFactoryInterface {

  use AwsServiceTrait;

  /**
   * The profile to use when initializing clients.
   *
   * @var \Drupal\aws\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * {@inheritdoc}
   */
  public function setProfile(ProfileInterface $profile) {
    $this->profile = $profile;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient($service_id) {
    $service = $this->aws->getService($service_id);

    if ($this->profile) {
      $profile = $this->profile;
    }
    else {
      $profile = $this->aws->getProfile($service_id);
    }

    if (!$profile) {
      return FALSE;
    }

    $class = "\Aws\\{$service['namespace']}\\{$service['namespace']}Client";
    return new $class($profile->getClientArgs());
  }

}
