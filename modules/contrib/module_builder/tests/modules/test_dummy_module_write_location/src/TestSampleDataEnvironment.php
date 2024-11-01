<?php

namespace Drupal\test_dummy_module_write_location;

use DrupalCodeBuilder\Environment\DrupalLibrary;

class TestSampleDataEnvironment extends DrupalLibrary {

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'TestExportInclude';

  /**
   * Set the hooks directory.
   */
  function getHooksDirectorySetting() {
    // TODO: find a way to get the actual package location from Composer.
    $directory = '../vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
