<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the page cache in conjunction with Group-specific features.
 *
 * @group group
 */
class PageCacheTest extends GroupBrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->groupType = $this->createGroupType(['creator_membership' => FALSE]);
    $this->group = $this->createGroup(['type' => $this->groupType->id()]);

    $outsider_base = [
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'permissions' => ['view group'],
    ];
    $this->createGroupRole(['global_role' => RoleInterface::ANONYMOUS_ID] + $outsider_base);
    $this->createGroupRole(['global_role' => RoleInterface::AUTHENTICATED_ID] + $outsider_base);
  }

  /**
   * Tests the automatic presence of the anonymous user's group cache tags.
   *
   * @see \Drupal\group\EventSubscriber\AnonymousUserResponseSubscriber
   */
  public function testPageCacheAnonymousGroupPermissions() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $allowed_url = $this->group->toUrl();
    $forbidden_url = $this->group->toUrl('edit-form');

    // Get cache tags associated with the anonymous user's group permissions.
    /** @var \Drupal\group\Access\GroupPermissionCalculatorInterface $group_permission_calculator */
    $group_permission_calculator = \Drupal::service('group_permission.calculator');
    $anonymous_permissions = $group_permission_calculator->calculateFullPermissions(\Drupal::currentUser());
    $anonymous_cache_tags = $anonymous_permissions->getCacheTags();

    // 1. anonymous user, without permission.
    $this->drupalGet($forbidden_url);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('user.group_permissions');
    foreach ($anonymous_cache_tags as $anonymous_cache_tag) {
      $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $anonymous_cache_tag);
    }

    // 2. anonymous user, with permission.
    $this->drupalGet($allowed_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user.group_permissions');
    foreach ($anonymous_cache_tags as $anonymous_cache_tag) {
      $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $anonymous_cache_tag);
    }

    // Log in as any user.
    $auth_user = $this->drupalCreateUser();
    $this->drupalLogin($auth_user);

    // 3. authenticated user, without permission.
    $this->drupalGet($forbidden_url);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('user.group_permissions');
    foreach ($anonymous_cache_tags as $anonymous_cache_tag) {
      $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', $anonymous_cache_tag);
    }

    // 4. authenticated user, with permission.
    $this->drupalGet($allowed_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user.group_permissions');
    foreach ($anonymous_cache_tags as $anonymous_cache_tag) {
      $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', $anonymous_cache_tag);
    }
  }

}
