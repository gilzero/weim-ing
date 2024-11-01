<?php

namespace Drupal\test_dummy_module_write_location;

use Drupal\Core\File\FileSystemInterface;
use Drupal\module_builder\ModuleFileWriter;

/**
 * Test file writer which puts module files in the test site folder.
 *
 * This ensures that they are cleaned up, and do not interfere with site code.
 */
class ModuleFileWriterDummyLocation extends ModuleFileWriter {

  /**
   * Put modules in the site folder, as that gets cleaned up.
   */
  public function getRelativeModuleFolder($module_name) {
    $site_path = \Drupal::service('site.path');

    return $site_path . '/' . $module_name;
  }

}
