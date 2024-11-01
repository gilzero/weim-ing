<?php

declare(strict_types=1);

namespace Drupal\Tests\monolog\Kernel;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Monolog module.
 */
class MonologKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['monolog'];

  /**
   * Test the Monolog service.
   */
  public function testMonolog(): void {
    $monolog = $this->container->get('logger.factory')->get('monolog');
    $this->assertInstanceOf(LoggerChannelInterface::class, $monolog);
  }

}
