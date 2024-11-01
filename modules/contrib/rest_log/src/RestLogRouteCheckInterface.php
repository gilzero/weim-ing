<?php

namespace Drupal\rest_log;

/**
 * Route check service determines logging rules for particular routes.
 */
interface RestLogRouteCheckInterface {

  /**
   * Determines whether the rest logging applies to a specific route or not.
   *
   * @return bool
   *   TRUE if this route should be logged.
   */
  public function applies();

}
