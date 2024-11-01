<?php

namespace Drupal\Tests\aws\Kernel;

/**
 * Tests the AWS Profile entity.
 *
 * @group aws
 */
class ProfileTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $storage = $this->entityTypeManager->getStorage('aws_profile');
    $this->profile = $storage->load('aws_test');
  }

  /**
   * Tests checking if a profile is the default.
   *
   * @covers ::isDefault
   */
  public function testIsDefault() {
    $this->assertTrue($this->profile->isDefault());
  }

  /**
   * Tests setting the default status of a profile.
   *
   * @covers ::setDefault
   */
  public function testSetDefault() {
    $this->profile->setDefault(FALSE);
    $this->assertFalse($this->profile->isDefault());
  }

  /**
   * Tests getting the access key.
   *
   * @covers ::getAccessKey
   */
  public function testGetAccessKey() {
    $this->assertEquals('TestAccessKey', $this->profile->getAccessKey());
  }

  /**
   * Tests setting the access key.
   *
   * @covers ::setAccessKey
   */
  public function testSetAccessKey() {
    $key = $this->randomString();

    $this->profile->setAccessKey($key);
    $this->assertEquals($key, $this->profile->getAccessKey());
  }

  /**
   * Tests getting the secret access key.
   *
   * @covers ::getSecretAccessKey
   */
  public function testGetSecretAccessKey() {
    $this->assertEquals('TestSecretKey', $this->profile->getSecretAccessKey());
  }

  /**
   * Tests setting the secret access key.
   *
   * @covers ::setSecretAccessKey
   */
  public function testSetSecretAccessKey() {
    $key = $this->randomString();

    $this->profile->setSecretAccessKey($key);
    $this->assertEquals($key, $this->profile->getSecretAccessKey());
  }

  /**
   * Tests getting the role arn.
   *
   * @covers ::getRoleArn
   */
  public function testGetRoleArn() {
    $this->assertEquals('arn:aws:iam::acount:role/TestRole', $this->profile->getRoleArn());
  }

  /**
   * Tests setting the role arn.
   *
   * @covers ::setRoleArn
   */
  public function testSetRoleArn() {
    $arn = $this->randomString();

    $this->profile->setRoleArn($arn);
    $this->assertEquals($arn, $this->profile->getRoleArn());
  }

  /**
   * Tests getting the role session name.
   *
   * @covers ::getRoleSessionName
   */
  public function testGetRoleSessionName() {
    $this->assertEquals('TestSessionName', $this->profile->getRoleSessionName());
  }

  /**
   * Tests setting the role session name.
   *
   * @covers ::setRoleSessionName
   */
  public function testSetRoleSessionName() {
    $name = $this->randomString();

    $this->profile->setRoleSessionName($name);
    $this->assertEquals($name, $this->profile->getRoleSessionName());
  }

}
