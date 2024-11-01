<?php

namespace Drupal\rest_log_test\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Class used to test rest log module.
 *
 * @RestResource(
 *   id = "rest_log_test",
 *   label = @Translation("Rest log test"),
 *   uri_paths = {
 *     "canonical" = "/rest-log-test"
 *   }
 * )
 */
class RestLogTestResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get() {
    return new ResourceResponse(['message' => 'GET TEST']);
  }

}
