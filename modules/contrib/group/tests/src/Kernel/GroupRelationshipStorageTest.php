<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\group\Entity\Storage\ConfigWrapperStorageInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests the behavior of relationship storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupRelationshipStorage
 * @group group
 */
class GroupRelationshipStorageTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_test_plugin', 'node'];

  /**
   * The relationship storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface
   */
  protected $storage;

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = $this->entityTypeManager->getStorage('group_relationship');
    $this->groupType = $this->createGroupType();

    // Enable the test plugins on a test group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($this->groupType, 'user_relation')->save();
    $storage->createFromPlugin($this->groupType, 'group_relation')->save();
    $storage->createFromPlugin($this->groupType, 'node_type_relation')->save();
  }

  /**
   * Creates an unsaved group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createUnsavedGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => $this->groupType->id(),
      'label' => $this->randomMachineName(),
    ]);
    return $group;
  }

  /**
   * Creates an unsaved user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUnsavedUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    return $account;
  }

  /**
   * Tests the creation of a GroupRelationship entity using an unsaved group.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an entity to an unsaved group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_relation');
  }

  /**
   * Tests the creation of a GroupRelationship entity using an unsaved entity.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedEntity() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUnsavedUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an unsaved entity to a group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_relation');
  }

  /**
   * Tests the creation of a GroupRelationship entity using incorrect plugin ID.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidPluginId() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Invalid plugin provided for adding the entity to the group.');
    $this->storage->createForEntityInGroup($account, $group, 'group_relation');
  }

  /**
   * Tests the creation of a GroupRelationship entity using an incorrect bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $subgroup = $this->createGroup(['type' => $this->createGroupType()->id()]);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage("The provided plugin provided does not support the entity's bundle.");
    $this->storage->createForEntityInGroup($subgroup, $group, 'group_relation');
  }

  /**
   * Tests the creation of a GroupRelationship entity using a bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $subgroup = $this->createGroup(['type' => $this->createGroupType(['id' => 'default'])->id()]);
    $group_relationship = $this->storage->createForEntityInGroup($subgroup, $group, 'group_relation');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupRelationshipInterface', $group_relationship, 'Created a GroupRelationship entity using a bundle-specific plugin.');
  }

  /**
   * Tests the creation of a GroupRelationship entity using no bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithoutBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUser();
    $group_relationship = $this->storage->createForEntityInGroup($account, $group, 'user_relation');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupRelationshipInterface', $group_relationship, 'Created a GroupRelationship entity using a bundle-independent plugin.');
  }

  /**
   * Tests the creation of a GroupRelationship entity using a config entity.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForConfig() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $node_type = $this->createNodeType();

    $group_relationship = $this->storage->createForEntityInGroup($node_type, $group, 'node_type_relation');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupRelationshipInterface', $group_relationship, 'Created a GroupRelationship entity using a config handling plugin.');

    $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
    assert($storage instanceof ConfigWrapperStorageInterface);
    $wrapper = $storage->wrapEntity($node_type);
    $this->assertSame($wrapper->id(), $group_relationship->get('entity_id')->target_id);
  }

  /**
   * Tests the loading of GroupRelationship entities for an unsaved group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $this->assertSame([], $this->storage->loadByGroup($group));
  }

  /**
   * Tests the loading of GroupRelationship entities for a group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByGroup() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $this->assertCount(1, $this->storage->loadByGroup($group), 'Managed to load the group creator membership by group.');
    $this->assertCount(1, $this->storage->loadByGroup($group, 'group_membership'), 'Managed to load the group creator membership by group and plugin ID.');
  }

  /**
   * Tests the loading of GroupRelationship entities for an unsaved entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByUnsavedEntity() {
    $group = $this->createUnsavedGroup();
    $this->assertSame([], $this->storage->loadByEntity($group));
  }

  /**
   * Tests the loading of GroupRelationship entities for an unsupported entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByUnsupportedEntity() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Loading relationships for the given entity of type "group" not supported by the provided plugin "user_relation".');
    $this->storage->loadByEntity($group, 'user_relation');
  }

  /**
   * Tests the loading of GroupRelationship entities for a content entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByContentEntity() {
    $group_a = $this->createGroup(['type' => $this->groupType->id()]);
    $group_b = $this->createGroup(['type' => $this->createGroupType(['id' => 'default'])->id()]);
    $account = $this->getCurrentUser();

    // Both entities should have ID 2 to test.
    $this->assertSame($group_b->id(), $account->id());

    // Relate the group so we can verify only the user is returned.
    $group_a->addRelationship($group_b, 'group_relation');
    $this->assertCount(2, $this->storage->loadByEntity($account), 'Managed to load the group creator memberships by user.');
    $this->assertCount(2, $this->storage->loadByEntity($account, 'group_membership'), 'Managed to load the group creator memberships by user and plugin ID.');
  }

  /**
   * Tests the loading of GroupRelationship entities for a config entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByConfigEntity() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $node_type = $this->createNodeType();
    $group->addRelationship($node_type, 'node_type_relation');

    $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
    assert($storage instanceof ConfigWrapperStorageInterface);
    $wrapper = $storage->wrapEntity($node_type);

    $group_relationships = $this->storage->loadByEntity($node_type);
    $this->assertCount(1, $group_relationships, 'Managed to load the grouped node types by node type.');
    $this->assertSame($wrapper->id(), reset($group_relationships)->get('entity_id')->target_id);

    $group_relationships = $this->storage->loadByEntity($node_type, 'node_type_relation');
    $this->assertCount(1, $group_relationships, 'Managed to load the grouped node types by node type and plugin ID.');
    $this->assertSame($wrapper->id(), reset($group_relationships)->get('entity_id')->target_id);
  }

  /**
   * Tests the loading of group relationships for a content entity and group.
   *
   * @covers ::loadByEntityAndGroup
   */
  public function testLoadByContentEntityAndGroup() {
    $group_a = $this->createGroup(['type' => $this->groupType->id()]);
    $group_b = $this->createGroup(['type' => $this->createGroupType(['id' => 'default'])->id()]);
    $account = $this->getCurrentUser();

    // Both entities should have ID 2 to test.
    $this->assertSame($group_b->id(), $account->id());

    // Add the group as content so we can verify only the user is returned.
    $group_a->addRelationship($group_b, 'group_relation');
    $this->assertCount(1, $this->storage->loadByEntityAndGroup($account, $group_a), 'Managed to load the group creator membership by user and group.');
    $this->assertCount(1, $this->storage->loadByEntityAndGroup($account, $group_a, 'group_membership'), 'Managed to load the group creator membership by user, group and plugin ID.');
  }

  /**
   * Tests the loading of group relationships for a config entity and group.
   *
   * @covers ::loadByEntityAndGroup
   */
  public function testLoadByConfigEntityAndGroup() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $node_type = $this->createNodeType();
    $group->addRelationship($node_type, 'node_type_relation');

    $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
    assert($storage instanceof ConfigWrapperStorageInterface);
    $wrapper = $storage->wrapEntity($node_type);

    $group_relationships = $this->storage->loadByEntityAndGroup($node_type, $group);
    $this->assertCount(1, $group_relationships, 'Managed to load the grouped node types by node type and group.');
    $this->assertSame($wrapper->id(), reset($group_relationships)->get('entity_id')->target_id);

    $group_relationships = $this->storage->loadByEntityAndGroup($node_type, $group, 'node_type_relation');
    $this->assertCount(1, $group_relationships, 'Managed to load the grouped node types by node type, group and plugin ID.');
    $this->assertSame($wrapper->id(), reset($group_relationships)->get('entity_id')->target_id);
  }

  /**
   * Tests the loading of GroupRelationship entities for an entity.
   *
   * @covers ::loadByPluginId
   */
  public function testLoadByPluginId() {
    $this->createGroup(['type' => $this->groupType->id()]);
    $this->assertCount(1, $this->storage->loadByPluginId('group_membership'), 'Managed to load the group creator membership by plugin ID.');
  }

  /**
   * Tests that shared bundle classes work.
   *
   * @covers ::getEntityClass
   */
  public function testSharedBundleClass() {
    $group_type_none = $this->createGroupType();
    $group_type_shared = $this->createGroupType();
    $group_type_specific = $this->createGroupType();
    $account = $this->createUser();

    // Enable the test plugins on the test group types.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($group_type_none, 'user_relation')->save();
    $storage->createFromPlugin($group_type_shared, 'user_relation_shared_bundle_class')->save();
    $storage->createFromPlugin($group_type_specific, 'user_relation_shared_bundle_class')->save();

    // Verify that the group types use the expected entity class.
    $group_relationship_none = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_none->id()]), 'user_relation');
    $group_relationship_shared = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_shared->id()]), 'user_relation_shared_bundle_class');
    $group_relationship_specific = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_specific->id()]), 'user_relation_shared_bundle_class');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupRelationship', $group_relationship_none, 'Regular entity class detected when no specific or shared bundle class was present.');
    $this->assertInstanceOf('\Drupal\group_test_plugin\Entity\GroupedUser', $group_relationship_shared, 'Shared bundle class detected when no specific bundle class was present.');
    $this->assertInstanceOf('\Drupal\group_test_plugin\Entity\GroupedUser', $group_relationship_specific, 'Shared bundle class detected when no specific bundle class was present.');

    // Enable a specific bundle class.
    $GLOBALS['specific_bundle_id'] = $storage->getRelationshipTypeId($group_type_specific->id(), 'user_relation_shared_bundle_class');
    $this->entityTypeManager->clearCachedDefinitions();
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Verify again, checking that the specific bundle class takes precedence.
    $group_relationship_none = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_none->id()]), 'user_relation');
    $group_relationship_shared = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_shared->id()]), 'user_relation_shared_bundle_class');
    $group_relationship_specific = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_specific->id()]), 'user_relation_shared_bundle_class');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupRelationship', $group_relationship_none, 'Regular entity class detected when no specific or shared bundle class was present.');
    $this->assertInstanceOf('\Drupal\group_test_plugin\Entity\GroupedUser', $group_relationship_shared, 'Shared bundle class detected when no specific bundle class was present.');
    $this->assertInstanceOf('\Drupal\group_test_plugin\Entity\GroupedUserSpecific', $group_relationship_specific, 'Specific bundle class detected when specific bundle class was present.');

    // Enable specific bundle class for the plugin without shared bundle class.
    $GLOBALS['specific_bundle_id'] = $storage->getRelationshipTypeId($group_type_none->id(), 'user_relation');
    $this->entityTypeManager->clearCachedDefinitions();
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Verify again, checking that the specific bundle class works.
    $group_relationship_none = $this->storage->createForEntityInGroup($account, $this->createGroup(['type' => $group_type_none->id()]), 'user_relation');
    $this->assertInstanceOf('\Drupal\group_test_plugin\Entity\GroupedUserSpecific', $group_relationship_none, 'Specific bundle class detected when no shared bundle class was present.');
  }

}
