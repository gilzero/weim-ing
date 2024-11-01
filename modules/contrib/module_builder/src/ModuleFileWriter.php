<?php

namespace Drupal\module_builder;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;

/**
 * Writes module files.
 */
class ModuleFileWriter {

  /**
   * The Module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Creates a ModuleFileWriter instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The Module extension list service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The File system service.
   */
  public function __construct(
    ModuleExtensionList $module_extension_list,
    FileSystemInterface $file_system
  ) {
    $this->moduleExtensionList = $module_extension_list;
    $this->fileSystem = $file_system;
  }

   /**
   * Get the Drupal-relative path of the module folder to write to.
   *
   * This considers the following options, in order:
   * - The location of an existing module of the given name.
   * - Inside the 'modules/custom' folder, if it exists.
   * - Inside the 'modules' folder.
   *
   * @param \Drupal\module_builder\Entity\ComponentInterface|string $module
   *   The module entity to get the path for, or the module machine name.
   *   Passing the machine name is deprecated and will be removed in 5.x.
   *
   * @return string
   *   The Drupal-relative path of the module folder. This does not yet
   *   necessarily exist.
   */
  public function getRelativeModuleFolder($module) {
    if (is_string($module)) {
      $module_name = $module;
      $module = \Drupal::service('entity_type.manager')->getStorage('module_builder_module')->load($module_name);
    }
    else {
      $module_name = $module->id();
    }

    // If a location is specified by the module, write there.
    if (!empty($module->location)) {
      $modules_dir = $module->location;

      $drupal_relative_module_dir = $modules_dir . '/' . $module_name;

      return $drupal_relative_module_dir;
    }

    // If the module folder already exists, write there.
    $exists = \Drupal::service('extension.list.module')->exists($module_name);
    if ($exists) {
      $module = \Drupal::service('extension.list.module')->get($module_name);
      return $module->getPath();
    }

    if (file_exists('modules/custom')) {
      $modules_dir = 'modules/custom';
    }
    else {
      $modules_dir = 'modules';
    }

    $drupal_relative_module_dir = $modules_dir . '/' . $module_name;

    return $drupal_relative_module_dir;
  }

  /**
   * Writes a single file.
   *
   * @param string $drupal_relative_module_dir
   *   The module folder to write to, as a path relative to Drupal root.
   * @param string $module_relative_filepath
   *   The name of the file to write, as a path relative to the module folder,
   *   e.g. src/Plugins/Block/Foo.php.
   * @param string $file_contents
   *   The file contents to write.
   *
   * @return bool
   *   TRUE if writing succeeded, FALSE if it failed.
   */
  public function writeSingleFile($drupal_relative_module_dir, $module_relative_filepath, $file_contents) {
    // The files are keyed by a filepath relative to the future module folder,
    // e.g. src/Plugins/Block/Foo.php.
    // Extract the directory.
    $module_relative_dir = dirname($module_relative_filepath);
    $filename = basename($module_relative_filepath);

    $drupal_relative_dir      = $drupal_relative_module_dir . '/' . $module_relative_dir;
    $drupal_relative_filepath = $drupal_relative_module_dir . '/' . $module_relative_filepath;
    $directory_result = $this->fileSystem->prepareDirectory($drupal_relative_dir, FileSystemInterface::CREATE_DIRECTORY);
    if (!$directory_result) {
      return $directory_result;
    }

    $result = file_put_contents($drupal_relative_filepath, $file_contents);

    // Force the Core extension system to rescan for modules if we've written
    // a module info file, so that the reloaded form can detect the module and
    // warn for the existing files.
    if ($result !== FALSE && substr($filename, -8) == 'info.yml') {
      $this->moduleExtensionList->reset();
    }

    return ($result !== FALSE);
  }

}
