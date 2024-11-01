<?php

namespace Drupal\Tests\gnode\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group gnode
 */
class GroupNodeConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'gnode',
    'group',
    'node',
    'options',
    'views',
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installConfig(['gnode']);
  }

}
