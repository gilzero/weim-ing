<?php

namespace Drupal\raven\Logger;

use Sentry\ClientInterface;

/**
 * Defines the Raven logger interface.
 */
interface RavenInterface {

  /**
   * Returns existing or new Sentry client, or NULL if it could not be created.
   */
  public function getClient(bool $force_new = FALSE, bool $force_throw = FALSE): ?ClientInterface;

}
