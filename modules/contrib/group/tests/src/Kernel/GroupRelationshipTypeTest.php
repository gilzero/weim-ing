<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests for the GroupRelationshipType entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupRelationshipType
 */
class GroupRelationshipTypeTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_test_plugin', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * Tests that bundle info is recalculated when needed.
   *
   * @covers ::postSave
   * @uses group_entity_bundle_info
   */
  public function testBundleInfoCacheCleared() {
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    assert($bundle_info instanceof EntityTypeBundleInfo);

    // Assert that there are no bundles. Please note that a content entity type
    // must have at least one bundle so it defaults to the entity type ID.
    $this->assertSame(['group_config_wrapper'], array_keys($bundle_info->getBundleInfo('group_config_wrapper')));

    // Install a config handling plugin on a group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_relation'));

    // Assert that the cache was cleared and bundle declared.
    $this->assertSame(['node_type'], array_keys($bundle_info->getBundleInfo('group_config_wrapper')));
  }

  /**
   * Test that the relationship type label is generated from the plugin label.
   *
   * @covers ::label
   */
  public function testBundleLabel() {
    // Create a group type and enable relating users.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $group_relationship_type = $storage->load($storage->getRelationshipTypeId($group_type->id(), 'group_membership'));

    $group_relation_type = $group_type->getPlugin('group_membership')->getRelationType();
    $expected = new TranslatableMarkup('INTERNAL USE ONLY -- @group_type -- @plugin', [
      '@group_type' => $group_type->label(),
      '@plugin' => $group_relation_type->getLabel(),
    ]);
    $this->assertEquals($expected, $group_relationship_type->label());
  }

  /**
   * Tests that the entity type is defined as internal.
   */
  public function testIsInternal() {
    $this->assertTrue($this->entityTypeManager->getDefinition('group_relationship_type')->isInternal());
  }

}
