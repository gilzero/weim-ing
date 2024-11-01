<?php

namespace Drupal\rest_log\RestLogRouteCheck;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\rest_log\RestLogRouteCheckInterface;

/**
 * Checks if the route is rest page.
 */
class RestPageRouteCheck implements RestLogRouteCheckInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs an RestPageRouteCheck object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    $route = $this->currentRouteMatch->getRouteObject();
    return $route && $route->hasDefault('_rest_resource_config');
  }

}
