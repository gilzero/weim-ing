<?php

namespace Drupal\Tests\group_support_revisions\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\node\NodeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests that revision operations (do not) show up on a grouped entity.
 *
 * @group group_support_revisions
 */
class GroupSupportRevisionsTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'gnode'];

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'access content',
      'edit any page content',
      'delete any page content',
      'view all revisions',
      'revert all revisions',
      'delete all revisions',
      'access administration pages',
    ] + parent::getGlobalPermissions();
  }

  /**
   * The group type to run the tests with.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page', 'new_revision' => TRUE]);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->setUpAccount();

    $this->groupType = $this->createGroupType();
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupType, 'group_node:page'));

    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $group_role_storage->save($group_role_storage->create([
      'id' => 'foo',
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [],
    ]));
  }

  /**
   * Tests the revisions tab on an entity's canonical route.
   */
  public function testRevisionsTab(): void {
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $node = $this->drupalCreateNode(['type' => 'page', 'title' => $this->randomString()]);
    $path = '/node/1';
    $href = '/node/1/revisions';

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Control; "Revisions" tab shows up.');

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermission('view group_node:page entity'));

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addRelationship($node, 'group_node:page');

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Grouping the node without any special support still shows the "Revisions" tab.');

    \Drupal::getContainer()->get('module_installer')->install(['group_support_revisions'], TRUE);

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefNotExists($href, 'Now hat Group knows about revision operations, the grouped node no longer shows the "Revisions" tab.');

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermission('view all group_node:page entity revisions'));

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Assigning the right Group permissions once again shows the "Revisions" tab.');
  }

  /**
   * Tests the viewing of individual revisions for an entity.
   *
   * @depends testRevisionsTab
   */
  public function testViewRevision(): void {
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $node_storage = $this->entityTypeManager->getStorage('node');
    $path = '/node/1/revisions/1/view';

    $node = $this->drupalCreateNode(['type' => 'page', 'title' => 'First title']);
    $node = $node_storage->load($node->id());
    assert($node instanceof NodeInterface);
    $node->setNewRevision();
    $node_storage->save($node->setTitle('Second title'));

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermission('view group_node:page entity'));

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addRelationship($node, 'group_node:page');

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    \Drupal::getContainer()->get('module_installer')->install(['group_support_revisions'], TRUE);

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(403);

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermission('view group_node:page entity revisions'));

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the revision operations on an entity's version history route.
   *
   * @param string $name
   *   The name of the operation we expect to see.
   * @param string $href
   *   The href of the operation we expect to see.
   * @param string $crud_permission
   *   The permission of the same CRUD operation, required by core.
   * @param string $group_permission
   *   The group permission that should grant access when grouped and supported.
   *
   * @depends testRevisionsTab
   * @dataProvider revisionsOperationsProvider
   */
  public function testRevisionOperations(string $name, string $href, string $crud_permission, string $group_permission): void {
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    $node_storage = $this->entityTypeManager->getStorage('node');
    $path = '/node/1/revisions';

    $node = $this->drupalCreateNode(['type' => 'page', 'title' => 'First title']);
    $node = $node_storage->load($node->id());
    assert($node instanceof NodeInterface);
    $node->setNewRevision();
    $node_storage->save($node->setTitle('Second title'));

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Control; "' . $name . '" operation shows up.');

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermissions([
      'view group_node:page entity',
      'view all group_node:page entity revisions',
      // Standard revision access checks rely on update or delete access.
      $crud_permission,
    ]));

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addRelationship($node, 'group_node:page');

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Grouping the node without any special support still shows the "' . $name . '" operation.');

    \Drupal::getContainer()->get('module_installer')->install(['group_support_revisions'], TRUE);

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefNotExists($href, 'Now hat Group knows about revision operations, the grouped node no longer shows the "' . $name . '" operation.');

    $group_role = $group_role_storage->load('foo');
    $group_role_storage->save($group_role->grantPermission($group_permission));

    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($href, 0, 'Assigning the right Group permissions once again shows the "' . $name . '" operation.');
  }

  /**
   * Data provider for ::testRevisionOperations().
   *
   * @return array
   *   A list of test scenarios.
   */
  public function revisionsOperationsProvider(): array {
    $cases['revert'] = [
      'name' => 'Revert',
      'href' => 'node/1/revisions/1/revert',
      'crud_permission' => 'update any group_node:page entity',
      'group_permission' => 'revert group_node:page entity revisions',
    ];

    $cases['delete'] = [
      'name' => 'Delete',
      'href' => 'node/1/revisions/1/delete',
      'crud_permission' => 'delete any group_node:page entity',
      'group_permission' => 'delete group_node:page entity revisions',
    ];

    return $cases;
  }

}
