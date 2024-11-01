<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Class for testing query alters in a non-abstract manner.
 *
 * @group group
 */
class QueryAlterTangibleTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

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
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $storage;

  /**
   * The node access control handler.
   *
   * @var \Drupal\node\NodeAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['user', 'group_test_plugin']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');

    $this->storage = $this->entityTypeManager->getStorage('node');
    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('node');
    $this->createNodeType(['type' => 'page']);
    $this->createNodeType(['type' => 'article']);

    $this->groupTypeA = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $this->groupTypeB = $this->createGroupType(['id' => 'bar', 'creator_membership' => FALSE]);

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'user_relation'));
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'node_relation:page'));
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'node_relation:article'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'user_relation'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'node_relation:page'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'node_relation:article'));
  }

  /**
   * Test that synchronized access to one bundle does not expose the other.
   */
  public function testMultipleBundlesSynchronized() {
    $role_base = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any node_relation:page entity'],
    ];
    $role_a = $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_base);
    $role_b = $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_base);

    $page_a = $this->createNode(['type' => 'page']);
    $page_b = $this->createNode(['type' => 'page']);
    $article_a = $this->createNode(['type' => 'article']);
    $article_b = $this->createNode(['type' => 'article']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);

    $group_a->addRelationship($page_a, 'node_relation:page');
    $group_a->addRelationship($article_a, 'node_relation:article');
    $group_b->addRelationship($page_b, 'node_relation:page');
    $group_b->addRelationship($article_b, 'node_relation:article');

    $expected = [$page_a->id(), $page_b->id()];
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can only see the pages, but not the articles.');

    $role_a->grantPermission('view any node_relation:article entity')->save();
    $expected[] = $article_a->id();
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both pages and articles from group type A and only pages from group type B.');

    $role_b->grantPermission('view any node_relation:article entity')->save();
    $expected[] = $article_b->id();
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both pages and articles.');
  }

  /**
   * Test that individual access to one bundle does not expose the other.
   */
  public function testMultipleBundlesIndividual() {
    $role_base = [
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'permissions' => ['view any node_relation:page entity'],
    ];
    $role_a = $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_base);
    $role_b = $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_base);

    $page_a = $this->createNode(['type' => 'page']);
    $page_b = $this->createNode(['type' => 'page']);
    $article_a = $this->createNode(['type' => 'article']);
    $article_b = $this->createNode(['type' => 'article']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_a->addRelationship($this->getCurrentUser(), 'group_membership', ['group_roles' => [$role_a->id()]]);
    $group_b->addRelationship($this->getCurrentUser(), 'group_membership', ['group_roles' => [$role_b->id()]]);

    $group_a->addRelationship($page_a, 'node_relation:page');
    $group_a->addRelationship($article_a, 'node_relation:article');
    $group_b->addRelationship($page_b, 'node_relation:page');
    $group_b->addRelationship($article_b, 'node_relation:article');

    $expected = [$page_a->id(), $page_b->id()];
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can only see the pages, but not the articles.');

    $role_a->grantPermission('view any node_relation:article entity')->save();
    $expected[] = $article_a->id();
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both pages and articles from group A and only pages from group B.');

    $role_b->grantPermission('view any node_relation:article entity')->save();
    $expected[] = $article_b->id();
    $visible = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both pages and articles.');
  }

  /**
   * Test that synchronized access to one plugin does not expose the other.
   */
  public function testMultiplePluginsSynchronized() {
    $role_base = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view group_membership relationship'],
    ];
    $role_a = $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_base);
    $role_b = $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_base);

    $account_a = $this->createUser();
    $account_b = $this->createUser();

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);

    $membership_a = $group_a->addRelationship($account_a, 'group_membership');
    $relationship_a = $group_a->addRelationship($account_a, 'user_relation');
    $membership_b = $group_b->addRelationship($account_b, 'group_membership');
    $relationship_b = $group_b->addRelationship($account_b, 'user_relation');

    $expected = [$membership_a->id(), $membership_b->id()];
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can only see the members, but not the user relationships.');

    $role_a->grantPermission('view user_relation relationship')->save();
    $expected[] = $relationship_a->id();
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both members and user relationships from group type A and only members from group type B.');

    $role_b->grantPermission('view user_relation relationship')->save();
    $expected[] = $relationship_b->id();
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both members and user relationships.');
  }

  /**
   * Test that individual access to one plugin does not expose the other.
   */
  public function testMultiplePluginsIndividual() {
    $role_base = [
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'permissions' => ['view group_membership relationship'],
    ];
    $role_a = $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_base);
    $role_b = $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_base);

    $account_a = $this->getCurrentUser();
    $account_b = $this->createUser();

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);

    $membership_a = $group_a->addRelationship($account_a, 'group_membership', ['group_roles' => [$role_a->id()]]);
    $relationship_a = $group_a->addRelationship($account_b, 'user_relation');
    $membership_b = $group_b->addRelationship($account_a, 'group_membership', ['group_roles' => [$role_b->id()]]);
    $relationship_b = $group_b->addRelationship($account_b, 'user_relation');

    $expected = [$membership_a->id(), $membership_b->id()];
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can only see the members, but not the user relationships.');

    $role_a->grantPermission('view user_relation relationship')->save();
    $expected[] = $relationship_a->id();
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both members and user relationships from group A and only members from group B.');

    $role_b->grantPermission('view user_relation relationship')->save();
    $expected[] = $relationship_b->id();
    $visible = $this->entityTypeManager->getStorage('group_relationship')->getQuery()->accessCheck()->execute();
    $this->assertEqualsCanonicalizing($expected, array_values($visible), 'Can see both members and user relationships.');
  }

  /**
   * Creates a node.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node entity.
   */
  protected function createNode(array $values = []) {
    $node = $this->storage->create($values + [
      'title' => $this->randomString(),
    ]);
    $node->enforceIsNew();
    $this->storage->save($node);
    return $node;
  }

}
