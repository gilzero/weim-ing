<?php

namespace Drupal\userprotect\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for user related routes.
 *
 * Adds an access requirement for a role_delegation route.
 *
 * @package Drupal\userprotect\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('role_delegation.edit_form')) {
      $route->setRequirement('_userprotect_role_access_check', 'TRUE');
    }
  }

}
