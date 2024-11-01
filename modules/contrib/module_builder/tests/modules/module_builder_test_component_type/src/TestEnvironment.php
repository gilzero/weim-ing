<?php

namespace Drupal\module_builder_test_component_type;

use DrupalCodeBuilder\Environment\DrupalLibrary;

/**
 * Drupal Code Builder environment class for testing.
 */
class TestEnvironment extends DrupalLibrary {

  /**
   * Whether to skip the sanity tests.
   *
   * @see skipSanityCheck()
   */
  protected $skipSanity = TRUE;

  /**
   * The short class name of the storage helper to use.
   *
   * We replace this so that this environment does the same change as
   * \Drupal\module_builder_devel\Environment\ModuleBuilderDevel. This is so
   * that when this module and module_builder_devel are both enabled, which
   * causes the module_builder_devel.drupal_code_builder decorating service to
   * not actually be in place, the storage change that it exists to make still
   * happens.
   */
  protected $storageType = 'ExportInclude';

}
