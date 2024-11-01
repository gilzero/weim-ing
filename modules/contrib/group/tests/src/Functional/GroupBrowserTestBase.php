<?php

declare(strict_types=1);

namespace Drupal\Tests\group\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\group\Traits\GroupTestTrait;

/**
 * Provides a base class for Group functional tests.
 */
abstract class GroupBrowserTestBase extends BrowserTestBase {

  use GroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user with group creation rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupCreator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Make sure we do not use user 1.
    $this->createUser();
  }

  /**
   * Sets up the Drupal account.
   */
  protected function setUpAccount(): void {
    // Create a user that will serve as the group creator.
    $this->groupCreator = $this->createUser($this->getGlobalPermissions());
    $this->drupalLogin($this->groupCreator);
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
    ];
  }

}
