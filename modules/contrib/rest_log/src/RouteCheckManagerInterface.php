<?php

namespace Drupal\rest_log;

/**
 * Runs the added checkers to determine the route logging status.
 */
interface RouteCheckManagerInterface {

  /**
   * Adds a checker.
   *
   * @param \Drupal\rest_log\RestLogRouteCheckInterface $checker
   *   The checker.
   */
  public function addChecker(RestLogRouteCheckInterface $checker);

  /**
   * Gets all added checkers.
   *
   * @return \Drupal\rest_log\RestLogRouteCheckInterface[]
   *   The checkers.
   */
  public function getCheckers();

  /**
   * Checks if route should be logged.
   *
   * @return bool
   *   TRUE if the route should be logged, FALSE otherwise.
   */
  public function check();

}
