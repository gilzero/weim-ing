<?php

namespace Drupal\iconify_icons;

use Drupal\Core\File\FileSystemInterface;

/**
 * Service description.
 */
class IconsCache implements IconsCacheInterface {

  /**
   * The file handler.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an IconsCache object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Calculates a file system path to cache an icon based on its parameters.
   *
   * @param string $collection
   *   The collection.
   * @param string $icon_name
   *   The icon name.
   * @param array $query_options
   *   Query options.
   *
   * @return string
   *   The Path in drupal file system.
   */
  protected function iconSettingsToPath(string $collection, string $icon_name, array $query_options) {
    return 'public://iconify-icons/' . implode('/', [
      $collection,
      $icon_name,
      $query_options['width'],
      $query_options['height'],
      $query_options['color'],
      $query_options['flip'],
      $query_options['rotate'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(string $collection, string $icon_name, array $query_options): string {
    // Translate settings to path.
    $path = $this->iconSettingsToPath($collection, $icon_name, $query_options);

    if ($this->fileSystem->realpath($path . '/' . $icon_name . '.svg')) {
      return file_get_contents($path . '/' . $icon_name . '.svg');
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setIcon(string $collection, string $icon_name, string $icon, array $parameters): bool {
    $directory = $this->iconSettingsToPath($collection, $icon_name, $parameters);
    if ($this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      $filepath = $directory . '/' . $icon_name . '.svg';
      $this->fileSystem->saveData($icon, $filepath, FileSystemInterface::EXISTS_REPLACE);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkIcon(string $collection, string $icon_name, array $parameters): bool {
    // Translate settings to path.
    $path = $this->iconSettingsToPath($collection, $icon_name, $parameters);

    if ($this->fileSystem->realpath($path . '/' . $icon_name . '.svg')) {
      return TRUE;
    }
    return FALSE;
  }

}
