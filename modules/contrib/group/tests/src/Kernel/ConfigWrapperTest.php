<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests for the ConfigWrapper entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\ConfigWrapper
 */
class ConfigWrapperTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_test_plugin', 'node'];

  /**
   * The config wrapper storage.
   *
   * @var \Drupal\group\Entity\Storage\ConfigWrapperStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->storage = $this->entityTypeManager->getStorage('group_config_wrapper');
  }

  /**
   * Tests the wrapped config entity getter.
   *
   * @covers ::getConfigEntity
   */
  public function testGetConfigEntity() {
    $this->installNodeTypePlugin();

    $node_type = $this->createNodeType();
    $wrapper = $this->storage->wrapEntity($node_type);
    $wrapped = $wrapper->getConfigEntity();

    $this->assertEquals($node_type->id(), $wrapped->id());
    $this->assertEquals($node_type->getEntityTypeId(), $wrapped->getEntityTypeId());
  }

  /**
   * Tests the wrapped config entity ID getter.
   *
   * @covers ::testGetConfigEntityId
   */
  public function testGetConfigEntityId() {
    $this->installNodeTypePlugin();
    $node_type = $this->createNodeType();
    $wrapper = $this->storage->wrapEntity($node_type);
    $this->assertEquals($node_type->id(), $wrapper->getConfigEntityId());
  }

  /**
   * Tests that wrappers are deleted along with their config entity.
   *
   * @covers \group_entity_delete
   */
  public function testDeleteConfigEntity() {
    $this->installNodeTypePlugin();

    $node_type = $this->createNodeType();
    $this->storage->wrapEntity($node_type);

    $properties = ['bundle' => 'node_type', 'entity_id' => $node_type->id()];
    $this->assertNotEmpty($this->storage->loadByProperties($properties));
    $node_type->delete();
    $this->assertEmpty($this->storage->loadByProperties($properties));
  }

  /**
   * Tests that everything works with wrappers unsupported.
   *
   * @covers \group_entity_delete
   */
  public function testDeleteConfigEntityNoWrapper() {
    $node_type = $this->createNodeType();
    $properties = ['bundle' => 'node_type', 'entity_id' => $node_type->id()];
    $this->assertEmpty($this->storage->loadByProperties($properties));
    $node_type->delete();
    $this->assertEmpty($this->storage->loadByProperties($properties));
  }

  /**
   * Installs a node type handling plugin on a group type.
   */
  protected function installNodeTypePlugin(): void {
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_relation'));
  }

}
