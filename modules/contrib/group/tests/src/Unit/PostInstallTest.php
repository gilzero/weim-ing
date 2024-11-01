<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\RelationHandlerDefault\PostInstall;

/**
 * Tests the default group relation post_install handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\PostInstall
 * @group group
 */
class PostInstallTest extends UnitTestCase {

  /**
   * Tests the install task getter.
   *
   * @covers ::getInstallTasks
   */
  public function testGetInstallTasks() {
    $post_install_handler = new PostInstall();
    $post_install_handler->init('foo', new GroupRelationType([]));
    $this->assertEquals([], $post_install_handler->getInstallTasks(), 'By default, there are no post install tasks.');
  }

}
