<?php

namespace Drupal\Tests\aws\Kernel;

use Aws\S3\S3Client;
use Aws\SesV2\SesV2Client;
use Aws\Sns\SnsClient;

/**
 * Tests the AWS client factory.
 *
 * @group aws
 */
class AwsClientFactoryTest extends KernelTestBase {

  /**
   * The AWS client factory.
   *
   * @var \Drupal\aws\AwsClientFactoryInterface
   */
  protected $clientFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->clientFactory = $this->container->get('aws.client_factory');
  }

  /**
   * Tests getting a few common service clients.
   *
   * @covers ::getClient
   */
  public function testGetClient() {
    $s3 = $this->clientFactory->getClient('s3');
    $this->assertInstanceOf(S3Client::class, $s3);

    $ses = $this->clientFactory->getClient('sesv2');
    $this->assertInstanceOf(SesV2Client::class, $ses);

    $sns = $this->clientFactory->getClient('sns');
    $this->assertInstanceOf(SnsClient::class, $sns);
  }

}
