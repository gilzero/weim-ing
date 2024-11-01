<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Unit\Integration\Event;

use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase as RulesEventTestBase;
use Drupal\rules\Core\RulesEventManager;

/**
 * Base class containing common code for regcode event tests.
 *
 * @group regcode
 */
abstract class EventTestBase extends RulesEventTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('regcode', [
      'Drupal\\regcode' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['regcode' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal(), $this->entityTypeBundleInfo->reveal());
  }

}
