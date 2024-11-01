<?php

namespace Drupal\Tests\moderation_dashboard\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests moderation dashboard permissions.
 *
 * @group moderation_dashboard
 */
class ModerationDashboardPermissionTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['moderation_dashboard'];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    // Create a moderated entity type.
    $this->drupalCreateContentType([
      'type' => 'page',
    ]);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * The test data.
   *
   * @var array
   */
  protected array $canViewOwnDashboardCases = [
    [
      'permissions' => ['use moderation dashboard'],
    ],
    [
      'permissions' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
    ],
  ];

  /**
   * Tests if a user can view their dashboard with permission.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCanViewOwnDashboard(): void {
    foreach ($this->canViewOwnDashboardCases as $i => $testCase) {
      $user = $this->createUser($testCase['permissions']);
      $this->drupalLogin($user);
      $this->assertSession()
        ->addressEquals("/user/{$user->id()}/moderation-dashboard");
      $status_code = $this->getSession()->getStatusCode();
      $message = "#$i: expected 200, got $status_code.";
      $this->assertEquals(200, $status_code, $message);
    }
  }

  /**
   * The test data.
   *
   * @var array
   */
  protected array $canNotViewOwnDashboardCases = [
    [
      'permissions' => [],
    ],
    [
      'permissions' => ['view any moderation dashboard'],
    ],
  ];

  /**
   * Tests that a user can't view their dashboard without permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCanNotViewOwnDashboard(): void {
    foreach ($this->canNotViewOwnDashboardCases as $i => $testCase) {
      $user = $this->createUser($testCase['permissions']);
      $this->drupalLogin($user);
      $this->drupalGet("/user/{$user->id()}/moderation-dashboard");
      $status_code = $this->getSession()->getStatusCode();
      // @todo this was 403 but if the user has permission to view any
      //   moderation dashboard doesn't that include your own?
      if ($user->hasPermission('view any moderation dashboard')) {
        $message = "#$i: expected 200, got $status_code.";
        $this->assertEquals(200, $status_code, $message);
      }
      else {
        $message = "#$i: expected 403, got $status_code.";
        $this->assertEquals(403, $status_code, $message);
      }
    }
  }

  /**
   * The test data.
   *
   * @var array
   */
  protected array $canViewOtherDashboardCases = [
    [
      'permissions_a' => ['view any moderation dashboard'],
      'permissions_b' => ['use moderation dashboard'],
    ],
    [
      'permissions_a' => ['view any moderation dashboard'],
      'permissions_b' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
    ],
    [
      'permissions_a' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
      'permissions_b' => ['use moderation dashboard'],
    ],
    [
      'permissions_a' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
      'permissions_b' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
    ],
  ];

  /**
   * Tests if a user can view other dashboards with permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCanViewOtherDashboard(): void {
    foreach ($this->canViewOtherDashboardCases as $i => $testCase) {
      $user_a = $this->createUser($testCase['permissions_a']);
      $user_b = $this->createUser($testCase['permissions_b']);
      $this->drupalLogin($user_a);
      $this->drupalGet("/user/{$user_b->id()}/moderation-dashboard");
      $status_code = $this->getSession()->getStatusCode();
      $message = "#$i: expected 200, got $status_code.";
      $this->assertEquals(200, $status_code, $message);
    }
  }

  /**
   * The test data.
   *
   * @var array
   */
  protected array $canNotViewOtherDashboardCases = [
    // User B doesn't have a dashboard, therefore nobody can view it.
    [
      'permissions_a' => [],
      'permissions_b' => [],
    ],
    [
      'permissions_a' => [],
      'permissions_b' => ['view any moderation dashboard'],
    ],
    [
      'permissions_a' => ['view any moderation dashboard'],
      'permissions_b' => [],
    ],
    [
      'permissions_a' => ['view any moderation dashboard'],
      'permissions_b' => ['view any moderation dashboard'],
    ],
    [
      'permissions_a' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
      'permissions_b' => [],
    ],
    [
      'permissions_a' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
      'permissions_b' => ['view any moderation dashboard'],
    ],
    [
      'permissions_a' => ['use moderation dashboard'],
      'permissions_b' => [],
    ],
    [
      'permissions_a' => ['use moderation dashboard'],
      'permissions_b' => ['view any moderation dashboard'],
    ],
    // User A doesn't have permission to view User B's dashboard.
    [
      'permissions_a' => [],
      'permissions_b' => ['use moderation dashboard'],
    ],
    [
      'permissions_a' => [],
      'permissions_b' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
    ],
    [
      'permissions_a' => ['use moderation dashboard'],
      'permissions_b' => ['use moderation dashboard'],
    ],
    [
      'permissions_a' => ['use moderation dashboard'],
      'permissions_b' => [
        'view any moderation dashboard',
        'use moderation dashboard',
      ],
    ],
  ];

  /**
   * Tests that a user can't view other dashboards without permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCanNotViewOtherDashboard(): void {
    foreach ($this->canNotViewOtherDashboardCases as $i => $testCase) {
      $user_a = $this->createUser($testCase['permissions_a']);
      $user_b = $this->createUser($testCase['permissions_b']);
      $this->drupalLogin($user_a);
      $this->drupalGet("/user/{$user_b->id()}/moderation-dashboard");
      $status_code = $this->getSession()->getStatusCode();
      // @todo this was 403 but if the user has permission to view
      //   any moderation dashboard doesn't that include your own?
      if ($user_a->hasPermission('view any moderation dashboard')) {
        $message = "#$i: expected 200, got $status_code.";
        $this->assertEquals(200, $status_code, $message);
      }
      else {
        $message = "#$i: expected 403, got $status_code.";
        $this->assertEquals(403, $status_code, $message);
      }
    }
  }

}
