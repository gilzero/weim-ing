<?php

namespace Drupal\ultimenu;

use Drupal\blazy\BlazyInterface;

/**
 * Interface for Ultimenu plugins.
 */
interface UltimenuInterface extends BlazyInterface {

  /**
   * Returns the block manager.
   */
  public function blockManager();

  /**
   * Returns the Ultimenu settings.
   *
   * @param string $key
   *   The setting name.
   *
   * @return array|null
   *   The settings by its key/ name.
   */
  public function getSetting($key = NULL);

  /**
   * Return a shortcut for the default theme.
   */
  public function getThemeDefault();

}
