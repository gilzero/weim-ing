<?php

namespace Drupal\aws;

use Drupal\aws\Entity\ProfileInterface;

/**
 * Provides an interface defining the AWS client factory.
 */
interface AwsClientFactoryInterface {

  /**
   * Set the profile to use when initializing clients.
   *
   * @param \Drupal\aws\Entity\ProfileInterface $profile
   *   The service ID.
   *
   * @return $this
   */
  public function setProfile(ProfileInterface $profile);

  /**
   * Creates an AWS SesClient instance.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return \Aws\AwsClientInterface
   *   The client instance.
   */
  public function getClient($service_id);

}
