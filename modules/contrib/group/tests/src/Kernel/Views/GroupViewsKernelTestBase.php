<?php

declare(strict_types=1);

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\Tests\group\Traits\GroupTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Defines an abstract test base for group kernel tests for Views.
 */
abstract class GroupViewsKernelTestBase extends ViewsKernelTestBase {

  use GroupTestTrait {
    createGroup as traitCreateGroup;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'field',
    'flexible_permissions',
    'group',
    'group_test_views',
    'options',
    'text',
  ];

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('group_relationship_type');
    $this->installConfig(['group', 'field']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->groupType = $this->createGroupType();

    // Allow anyone full group access so query alters don't deny access.
    $role_config = [
      'group_type' => $this->groupType->id(),
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'admin' => TRUE,
    ];
    $this->createGroupRole(['scope' => PermissionScopeInterface::OUTSIDER_ID] + $role_config);
    $this->createGroupRole(['scope' => PermissionScopeInterface::INSIDER_ID] + $role_config);

    // Make sure we do not use user 1.
    $this->createUser();

    // Set the current user so group creation can rely on it.
    $this->container->get('current_user')->setAccount($this->createUser());

    ViewTestData::createTestViews(get_class($this), ['group_test_views']);
  }

  /**
   * Retrieves the results for this test's view.
   *
   * @return \Drupal\views\ResultRow[]
   *   A list of view results.
   */
  protected function getViewResults(): array {
    $view = Views::getView(reset($this::$testViews));
    $view->setDisplay();

    if ($view->preview()) {
      return $view->result;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function createGroup(array $values = []): GroupInterface {
    $values += ['type' => $this->groupType->id()];
    return $this->traitCreateGroup($values);
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\UserInterface
   *   The created user entity.
   */
  protected function createUser($values = []): UserInterface {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
