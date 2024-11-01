<?php

namespace Drupal\module_builder_devel\Environment;

use DrupalCodeBuilder\Environment\DrupalLibrary;

/**
 * Drupal Code Builder environment class for development.
 *
 * This sets the storage to ExportInclude, which makes the stored data human-
 * readable.
 */
class ModuleBuilderDevel extends DrupalLibrary {

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'ExportInclude';

}
