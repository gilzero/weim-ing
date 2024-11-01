<?php

namespace Drupal\Tests\aws\Kernel;

use Drupal\aws\Entity\ProfileInterface;

/**
 * Tests the AWS service.
 *
 * @group aws
 */
class AwsTest extends KernelTestBase {

  /**
   * The AWS service.
   *
   * @var \Drupal\aws\AwsInterface
   */
  protected $aws;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aws = $this->container->get('aws');
  }

  /**
   * Tests getting the available profiles.
   *
   * @covers ::getProfiles
   */
  public function testGetProfiles() {
    $profiles = $this->aws->getProfiles();

    $this->assertEquals('aws_test', key($profiles));
    $this->assertInstanceOf(ProfileInterface::class, reset($profiles));
  }

  /**
   * Tests getting the profile for a service.
   *
   * @covers ::getProfile
   */
  public function testGetProfile() {
    $profile = $this->aws->getProfile('s3');

    $this->assertEquals('aws_test', $profile->id());
    $this->assertInstanceOf(ProfileInterface::class, $profile);
  }

  /**
   * Tests getting the default profile.
   *
   * @covers ::getDefaultProfile
   */
  public function testGetDefaultProfile() {
    $profile = $this->aws->getDefaultProfile();

    $this->assertEquals('aws_test', $profile->id());
    $this->assertInstanceOf(ProfileInterface::class, $profile);
  }

  /**
   * Tests getting the available services.
   *
   * @covers ::getServices
   */
  public function testGetServices() {
    $services = $this->aws->getServices();

    // Check a few common services to ensure they exist.
    $this->assertTrue(array_key_exists('s3', $services));
    $this->assertTrue(array_key_exists('sesv2', $services));
    $this->assertTrue(array_key_exists('sns', $services));
  }

  /**
   * Tests getting a service.
   *
   * @covers ::getService
   */
  public function testGetService() {
    $service = $this->aws->getService('s3');

    $this->assertEquals('S3', $service['namespace']);
    $this->assertTrue(array_key_exists('latest', $service['versions']));
  }

  /**
   * Tests getting the config for a service.
   *
   * @covers ::getServiceConfig
   */
  public function testGetServiceConfig() {
    $settings = $this->aws->getServiceConfig('s3');

    $this->assertEquals('aws_test', $settings['profile']);
    $this->assertEquals('latest', $settings['version']);
  }

  /**
   * Tests getting the overridden services.
   *
   * @covers ::getOverrides
   */
  public function testGetOverrides() {
    $overrides = $this->aws->getOverrides();

    $this->assertTrue(array_key_exists('s3', $overrides));
    $this->assertEquals('aws_test', $overrides['s3']['profile']);
    $this->assertEquals('latest', $overrides['s3']['version']);
  }

}
