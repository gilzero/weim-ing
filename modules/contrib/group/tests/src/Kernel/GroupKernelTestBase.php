<?php

declare(strict_types=1);

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\group\Traits\GroupTestTrait;

/**
 * Defines an abstract test base for group kernel tests.
 */
abstract class GroupKernelTestBase extends EntityKernelTestBase {

  use GroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity', 'flexible_permissions', 'group', 'options'];

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->container->get('group_relation_type.manager');

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('group_config_wrapper');
    $this->installConfig(['group']);

    // Make sure we do not use user 1.
    $this->createUser();
    $this->setCurrentUser($this->createUser());
  }

  /**
   * Gets the current user so you can run some checks against them.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser(): AccountInterface {
    return $this->container->get('current_user')->getAccount();
  }

}
