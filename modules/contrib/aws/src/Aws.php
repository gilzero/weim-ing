<?php

declare(strict_types=1);

namespace Drupal\aws;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use function Aws\manifest;

/**
 * AWS service class.
 */
class Aws implements AwsInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Set the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfiles() {
    $storage = $this->entityTypeManager->getStorage('aws_profile');
    /** @var \Drupal\aws\Entity\ProfileInterface[] $profiles */
    $profiles = $storage->loadMultiple();

    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile(string $service_id) {
    $settings = $this->getServiceConfig($service_id);

    if (!$settings || $settings['profile'] == 'default') {
      return $this->getDefaultProfile();
    }

    $storage = $this->entityTypeManager->getStorage('aws_profile');
    /** @var \Drupal\aws\Entity\ProfileInterface $profile */
    $profile = $storage->load($settings['profile']);

    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProfile() {
    $storage = $this->entityTypeManager->getStorage('aws_profile');
    /** @var \Drupal\aws\Entity\ProfileInterface[] $profiles */
    $profiles = $storage->loadByProperties(['default' => TRUE]);

    return reset($profiles);
  }

  /**
   * {@inheritdoc}
   */
  public function getServices() {
    return manifest();
  }

  /**
   * {@inheritdoc}
   */
  public function getService(string $service_id) {
    return manifest($service_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceConfig(string $service_id) {
    if (!$service_id) {
      return [];
    }

    $config = $this->configFactory->get('aws.settings');
    $services = $config->get('services');

    return $services[$service_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setServiceConfig(string $service_id, ?array $settings) {
    $config = $this->configFactory->getEditable('aws.settings');
    $services = $config->get('services');

    if ($settings === NULL) {
      unset($services[$service_id]);
    }
    else {
      $services[$service_id] = $settings;
    }

    $config->set('services', $services);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverrides() {
    $config = $this->configFactory->get('aws.settings');
    $services = $config->get('services') ?? [];

    return $services;
  }

}
