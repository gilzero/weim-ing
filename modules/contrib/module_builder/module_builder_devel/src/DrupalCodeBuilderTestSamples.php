<?php

namespace Drupal\module_builder_devel;

use DrupalCodeBuilder\Factory;
use Drupal\module_builder\DrupalCodeBuilder;

/**
 * Alternative library wrapper service, to use the test samples environment.
 */
class DrupalCodeBuilderTestSamples extends DrupalCodeBuilder {

  /**
   * {@inheritdoc}
   */
  protected function doLoadLibrary() {
    Factory::setEnvironmentLocalClass('WriteTestsSampleLocation')
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}
