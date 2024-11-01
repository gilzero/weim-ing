<?php

namespace Drupal\rest_log;

/**
 * Default implementation of the route check manager.
 */
class RouteCheckManager implements RouteCheckManagerInterface {

  /**
   * The checkers.
   *
   * @var \Drupal\rest_log\RestLogRouteCheckInterface[]
   */
  protected $checkers = [];

  /**
   * {@inheritdoc}
   */
  public function addChecker(RestLogRouteCheckInterface $checker) {
    $this->checkers[] = $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckers() {
    return $this->checkers;
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    foreach ($this->checkers as $checker) {
      if ($checker->applies()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
