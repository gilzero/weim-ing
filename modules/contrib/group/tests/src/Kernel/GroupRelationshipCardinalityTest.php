<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests for the GroupRelationshipCardinality constraint.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Plugin\Validation\Constraint\GroupRelationshipCardinalityValidator
 */
class GroupRelationshipCardinalityTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_test_plugin', 'node'];

  /**
   * The first group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * The second group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * The relationship storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface
   */
  protected $relationshipStorage;

  /**
   * The relationship type storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface
   */
  protected $relationshipTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['user', 'group_test_plugin']);
    $this->installEntitySchema('entity_test_with_owner');

    $this->groupTypeA = $this->createGroupType();
    $this->groupTypeB = $this->createGroupType();
    $this->relationshipStorage = $this->entityTypeManager->getStorage('group_relationship');
    $this->relationshipTypeStorage = $this->entityTypeManager->getStorage('group_relationship_type');

    // Make sure members can view the group and grouped entity.
    $base = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view group', 'view any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $base);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $base);

    // Make sure the user can view the entities to be grouped.
    $this->setCurrentUser($this->createUser(['administer entity_test_with_owner content']));
  }

  /**
   * Tests the group cardinality part of the constraint.
   *
   * @covers ::validate
   */
  public function testGroupCardinality() {
    $relationship_type = $this->relationshipTypeStorage->createFromPlugin(
      $this->groupTypeA,
      'entity_test_relation',
      ['group_cardinality' => 1]
    );
    $this->relationshipTypeStorage->save($relationship_type);

    $relationship_type_b = $this->relationshipTypeStorage->createFromPlugin(
      $this->groupTypeB,
      'entity_test_relation',
      ['group_cardinality' => 1]
    );
    $this->relationshipTypeStorage->save($relationship_type_b);

    $entity = $this->createTestEntity();

    // Try creating a first relationship.
    $relationship = $this->relationshipStorage->createForEntityInGroup($entity, $this->createGroup(['type' => $this->groupTypeA->id()]), 'entity_test_relation');
    $violations = $relationship->validate();
    $this->assertEquals(0, $violations->count(), 'No violations when unsaved entity did not reach limit');

    // Save the relationship and check again.
    $this->relationshipStorage->save($relationship);
    $violations = $relationship->validate();
    $this->assertEquals(0, $violations->count(), 'No violations when saved entity did not reach limit');

    // Create a second one in a different group and check for violations.
    $relationship = $this->relationshipStorage->createForEntityInGroup($entity, $this->createGroup(['type' => $this->groupTypeA->id()]), 'entity_test_relation');
    $violations = $relationship->validate();
    $this->assertEquals(1, $violations->count(), 'Violation when unsaved entity reaches limit');
    $message = new TranslatableMarkup(
      '@field: %content has reached the maximum amount of groups of type %group_type it can be added to',
      [
        '@field' => $relationship->getFieldDefinition('entity_id')->getLabel(),
        '%content' => $entity->label(),
        '%group_type' => $this->groupTypeA->label(),
      ]
    );
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());

    // Create one in the second group type to prove that doesn't collide.
    $relationship = $this->relationshipStorage->createForEntityInGroup($entity, $this->createGroup(['type' => $this->groupTypeB->id()]), 'entity_test_relation');
    $violations = $relationship->validate();
    $this->assertEquals(0, $violations->count(), 'No violations when adding entity to a group of a different type');
  }

  /**
   * Tests the entity cardinality part of the constraint.
   *
   * @covers ::validate
   */
  public function testEntityCardinality() {
    $relationship_type = $this->relationshipTypeStorage->createFromPlugin(
      $this->groupTypeA,
      'entity_test_relation',
      ['entity_cardinality' => 1]
    );
    $this->relationshipTypeStorage->save($relationship_type);
    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $entity = $this->createTestEntity();

    // Try creating a first relationship.
    $relationship = $this->relationshipStorage->createForEntityInGroup($entity, $group, 'entity_test_relation');
    $violations = $relationship->validate();
    $this->assertEquals(0, $violations->count(), 'No violations when unsaved entity did not reach limit');

    // Save the relationship and check again.
    $this->relationshipStorage->save($relationship);
    $violations = $relationship->validate();
    $this->assertEquals(0, $violations->count(), 'No violations when saved entity did not reach limit');

    // Create a second one in a different group and check for violations.
    $relationship = $this->relationshipStorage->createForEntityInGroup($entity, $group, 'entity_test_relation');
    $violations = $relationship->validate();
    $this->assertEquals(1, $violations->count(), 'Violation when unsaved entity reaches limit');
    $message = new TranslatableMarkup(
      '@field: %content has reached the maximum amount of times it can be added to %group',
      [
        '@field' => $relationship->getFieldDefinition('entity_id')->getLabel(),
        '%content' => $entity->label(),
        '%group' => $group->label(),
      ]
    );
    $this->assertEquals((string) $message, (string) $violations->get(0)->getMessage());
  }

  /**
   * Creates a test entity.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The created test entity.
   */
  protected function createTestEntity(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('entity_test_with_owner');
    $test_entity = $storage->create($values + [
      'name' => $this->randomString(),
      'type' => $this->randomMachineName(),
    ]);
    $test_entity->enforceIsNew();
    $storage->save($test_entity);
    return $test_entity;
  }

}
