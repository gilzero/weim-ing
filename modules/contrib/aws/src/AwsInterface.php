<?php

namespace Drupal\aws;

/**
 * Provides an interface defining the AWS service.
 */
interface AwsInterface {

  /**
   * Get all profiles.
   *
   * @return \Drupal\aws\Entity\ProfileInterface[]
   *   An array of Profile objects.
   */
  public function getProfiles();

  /**
   * Get the profile for a given service.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return \Drupal\aws\Entity\ProfileInterface
   *   The profile for the service.
   */
  public function getProfile(string $service_id);

  /**
   * Get the default profile.
   *
   * @return \Drupal\aws\Entity\ProfileInterface
   *   The default profile.
   */
  public function getDefaultProfile();

  /**
   * Get all AWS services.
   *
   * @return array
   *   An array of AWS service definitions.
   */
  public function getServices();

  /**
   * Get the service definition for a given service.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return array
   *   The service definition.
   */
  public function getService(string $service_id);

  /**
   * Get the config for a given service.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return array
   *   The config for the service.
   */
  public function getServiceConfig(string $service_id);

  /**
   * Set the config for a given service.
   *
   * @param string $service_id
   *   The service ID.
   * @param array|null $settings
   *   The config for the service, or NULL to remove the config.
   */
  public function setServiceConfig(string $service_id, ?array $settings);

  /**
   * Get all service overrides.
   *
   * @return array
   *   An array of overriden services.
   */
  public function getOverrides();

}
