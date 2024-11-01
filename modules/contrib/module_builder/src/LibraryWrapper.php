<?php

namespace Drupal\module_builder;

use DrupalCodeBuilder\Factory;

/**
 * Quick and dirty wrapper class to load our library.
 *
 * TODO: Remove this.
 */
class LibraryWrapper {

  /**
   * Loads the Drupal Coder Builder library and sets the environment.
   *
   * @throws
   *  Throws an exception if the library can't be found.
   */
  public static function loadLibrary() {
    if (!class_exists(Factory::class)) {
      throw new \Exception("Mising library.");
    }

    // TODO: add an environment class with a more appropriate name.
    Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}
