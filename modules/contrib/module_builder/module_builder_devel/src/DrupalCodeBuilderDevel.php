<?php

namespace Drupal\module_builder_devel;

use DrupalCodeBuilder\Factory;
use Drupal\module_builder\DrupalCodeBuilder;
use Drupal\module_builder_devel\Environment\ModuleBuilderDevel;

/**
 * Replacement library wrapper service, to switch the environment.
 *
 * The replacement environment switches the storage so that analysis data is
 * human-readable.
 */
class DrupalCodeBuilderDevel extends DrupalCodeBuilder {

  /**
   * {@inheritdoc}
   */
  protected function doLoadLibrary() {
    $environment = new ModuleBuilderDevel;

    Factory::setEnvironment($environment)
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}