<?php

namespace Drupal\ultimenu;

/**
 * Interface for Ultimenu skins.
 */
interface UltimenuSkinInterface extends UltimenuInterface {

  /**
   * Retrieves stored CSS files for Ultimenu skin options.
   *
   * @return array
   *   An array of available CSS files.
   */
  public function getSkins();

  /**
   * A reversed process to convert an option into a full CSS skin path.
   *
   * This silly reversion ensures the setting will be intact when moving around
   * CSS files, or theme and module directory.
   *
   * @param string $path
   *   The path that should be converted to full CSS path.
   *
   * @return string
   *   The CSS path containing ultimenu skins.
   */
  public function getSkinPath($path);

  /**
   * Gets the skin basename.
   *
   * @param string $path
   *   The path to the CSS file.
   *
   * @return string
   *   The skin basename.
   */
  public function getName($path);

  /**
   * Clear the cache definitions.
   *
   * @param bool $all
   *   A flag to check if the clearing is affection all about Ultimenu.
   */
  public function clearCachedDefinitions($all = FALSE);

  /**
   * Returns available carets.
   */
  public function getCaretSkins(): array;

  /**
   * Returns available off-canvas menus.
   */
  public function getOffCanvasSkins(): array;

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild(): array;

}
