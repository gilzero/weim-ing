<?php

namespace Drupal\Tests\aws\Kernel;

/**
 * Tests the AWS Profile entity encryption functionality.
 *
 * @group aws
 */
class EncryptionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aws',
    'aws_test',
    'aws_encrypted_test',
    'encrypt',
    'real_aes',
    'key',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('aws_encrypted_test');

    $storage = $this->entityTypeManager->getStorage('aws_profile');
    $this->profile = $storage->load('aws_encrypted');
  }

  /**
   * Tests getting an encrypted secret access key.
   *
   * @covers ::getSecretAccessKey
   */
  public function testGetSecretAccessKey() {
    $this->assertEquals('TestSecretKey', $this->profile->getSecretAccessKey());
  }

  /**
   * Tests setting an encrypted secret access key.
   *
   * @covers ::setSecretAccessKey
   */
  public function testSetSecretAccessKey() {
    $key = $this->randomString();

    $this->profile->setSecretAccessKey($key);
    $this->assertEquals($key, $this->profile->getSecretAccessKey());
  }

}
