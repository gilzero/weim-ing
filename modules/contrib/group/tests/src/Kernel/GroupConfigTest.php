<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group group
 */
class GroupConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'entity',
    'flexible_permissions',
    'group',
    'options',
    'views',
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_role');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('group_relationship_type');
    $this->installConfig(['group']);
  }

}
