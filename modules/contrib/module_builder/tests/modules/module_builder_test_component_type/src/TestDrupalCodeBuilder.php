<?php

namespace Drupal\module_builder_test_component_type;

use DrupalCodeBuilder\Factory;
use Drupal\module_builder\DrupalCodeBuilder;

/**
 * Library wrapper service for tests.
 *
 * This switches the environment.
 */
class TestDrupalCodeBuilder extends DrupalCodeBuilder {

  /**
   * {@inheritdoc}
   */
  protected function doLoadLibrary() {
    $environment = new TestEnvironment();

    Factory::setEnvironment($environment)
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}