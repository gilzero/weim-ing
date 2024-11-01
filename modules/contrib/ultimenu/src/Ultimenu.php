<?php

namespace Drupal\ultimenu;

/**
 * Provides common Ultimenu utility methods.
 */
class Ultimenu {

  const CARET = '<span class="ultimenu__caret caret" aria-hidden="true"><i></i></span>';

  const TAGS = ['b', 'em', 'i', 'small', 'span', 'strong'];

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  public static function service($service) {
    return \Drupal::hasService($service) ? \Drupal::service($service) : NULL;
  }

  /**
   * Retrieves the path resolver.
   *
   * @return \Drupal\Core\Extension\ExtensionPathResolver|null
   *   The path resolver.
   */
  public static function pathResolver() {
    return self::service('extension.path.resolver');
  }

  /**
   * Returns the commonly used path, or just the base path.
   */
  public static function getPath($type, $name, $absolute = FALSE): ?string {
    if ($resolver = self::pathResolver()) {
      $path = $resolver->getPath($type, $name);

      return $absolute ? \base_path() . $path : $path;
    }
    return '';
  }

}
