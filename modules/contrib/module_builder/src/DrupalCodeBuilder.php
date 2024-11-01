<?php

namespace Drupal\module_builder;

use DrupalCodeBuilder\Factory;

/**
 * Service class that wraps around the DCB library, to make it injectable.
 */
class DrupalCodeBuilder {

  /**
   * Whether the library has been initialized.
   *
   * @var bool
   */
  protected $loaded = FALSE;

  /**
   * Gets a task handler from the library.
   *
   * Same parameters as \DrupalCodeBuilder\Factory::getTask().
   *
   * @param string $task_name
   *   The task name.
   * @param mixed $task_options
   *   (optional) Options for the task.
   *
   * @return
   *   The task object.
   */
  public function getTask($task_name, $task_options = NULL) {
    if (!$this->loaded) {
      $this->loadLibrary();
    }

    return Factory::getTask($task_name, $task_options);
  }

  /**
   * Loads the Drupal Coder Builder library and sets the environment.
   *
   * @throws \Exception
   *  Throws an exception if the library can't be found.
   */
  public function loadLibrary() {
    if (!class_exists(Factory::class)) {
      throw new \Exception("Mising library.");
    }

    $this->doLoadLibrary();

    $this->loaded = TRUE;
  }

  /**
   * Helper for loadLibrary() for ease of overriding.
   */
  protected function doLoadLibrary() {
    // TODO: add an environment class with a more appropriate name.
    Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}
