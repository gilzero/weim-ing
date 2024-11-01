<?php

namespace Drupal\iconify_icons;

/**
 * Interface for Cache service.
 */
interface IconsCacheInterface {

  /**
   * Gets the icon from cache and return empty string if it doesn't exist.
   *
   * @param string $collection
   *   The collection.
   * @param string $icon_name
   *   The icon name.
   * @param array $query_options
   *   Query options.
   *
   * @return string
   *   The svg or '' if it doesn't exist yet.
   */
  public function getIcon(string $collection, string $icon_name, array $query_options): string;

  /**
   * Set the icon in cache.
   *
   * @param string $collection
   *   The Path in drupal file system.
   * @param string $icon_name
   *   The icon name.
   * @param string $icon
   *   The svg or '' if it doesn't exist yet.
   * @param array $parameters
   *   Query options.
   *
   * @return bool
   *   returns TRUE if the method works correctly, FALSE otherwise.
   */
  public function setIcon(string $collection, string $icon_name, string $icon, array $parameters): bool;

  /**
   * Checks if an icon is in cache.
   *
   * @param string $collection
   *   The collection.
   * @param string $icon_name
   *   The icon name.
   * @param array $parameters
   *   Query options.
   *
   * @return bool
   *   True if the icon is in cache, false in other case.
   */
  public function checkIcon(string $collection, string $icon_name, array $parameters): bool;

}
