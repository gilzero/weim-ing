<?php

namespace Drupal\Tests\aws\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Base class for entity kernel tests.
 */
abstract class KernelTestBase extends EntityKernelTestBase {

  /**
   * The test AWS profile.
   *
   * @var \Drupal\aws\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aws',
    'aws_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('aws_profile');
    $this->installConfig('aws_test');
  }

}
