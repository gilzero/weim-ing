<?php

namespace Drupal\disable_login\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DisableLoginRouteSubscriber.
 *
 * Listens to the dynamic route events to add access check for /user/login.
 */
class DisableLoginRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Prevent random access to /user/login page.
    $route = $collection->get('user.login');
    $route->setRequirement('disable_login_access_check', 'TRUE');
  }

}
