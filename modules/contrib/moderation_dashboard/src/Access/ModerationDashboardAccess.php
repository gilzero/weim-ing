<?php

namespace Drupal\moderation_dashboard\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying the scheduler list of scheduled nodes.
 */
class ModerationDashboardAccess implements AccessCheckInterface {

  /**
   * Constructs a ScheduledListAccess object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(protected RouteMatchInterface $routeMatch) {
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route): bool {
    return $route->hasRequirement('_access_moderation_dashboard');
  }

  /**
   * Determine if the $account has access to the scheduled content list.
   *
   * The result will vary depending on whether the page being viewed is the
   * user
   * profile page or the scheduled content admin overview.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current account.
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $dashboard_owner = $account;
    $current_user_id = $this->routeMatch->getRawParameter('user');

    // If the current user is on their own dashboard, they can view it.
    if ($current_user_id === $dashboard_owner->id() && $dashboard_owner->hasPermission('use moderation dashboard')) {
      return AccessResult::allowed();
    }

    // If the given user doesn't have a dashboard, nobody can view it.
    if (!$dashboard_owner->hasPermission('use moderation dashboard') && !$dashboard_owner->hasPermission('view any moderation dashboard')) {
      return AccessResult::forbidden('User does not have access to view this dashboard.');
    }

    // But they can only view the dashboard of others with another permission.
    if ($dashboard_owner->hasPermission('view any moderation dashboard')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
