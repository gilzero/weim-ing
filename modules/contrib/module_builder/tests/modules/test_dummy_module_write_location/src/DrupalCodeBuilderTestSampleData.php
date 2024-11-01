<?php

namespace Drupal\test_dummy_module_write_location;

use DrupalCodeBuilder\Factory;
use Drupal\module_builder\DrupalCodeBuilder;

/**
 * Uses the code analysis data that's part of Drupal Code Builder's test suite.
 *
 * This allows tests to have working code analysis data without having to run
 * the analysis.
 */
class DrupalCodeBuilderTestSampleData extends DrupalCodeBuilder {

  /**
   * {@inheritdoc}
   */
  protected function doLoadLibrary() {
    $environment = new TestSampleDataEnvironment();

    Factory::setEnvironment($environment)
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}
