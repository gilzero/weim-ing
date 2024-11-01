<?php

namespace Drupal\ckeditor5_icons;

/**
 * Provides methods for retrieving Font Awesome metadata and styles.
 */
interface CKEditor5IconsInterface {

  /**
   * Gets the precise version designations of the Font Awesome libraries.
   *
   * @return array
   *   An array containing the precise version designations.
   */
  public function getPreciseLibraryVersions();

  /**
   * Gets the Font Awesome metadata path.
   *
   * @param string $faMetadataVersion
   *   The Font Awesome version to append.
   *
   * @return string
   *   The Font Awesome metadata path.
   */
  public function getFontAwesomeMetadataPath($faMetadataVersion);

  /**
   * Gets the Font Awesome category metadata.
   *
   * @param mixed $faVersion
   *   '5' for Font Awesome 5 metadata (optional).
   *
   * @return array
   *   The Font Awesome category metadata.
   */
  public function getFontAwesomeCategories($faVersion);

  /**
   * Gets the Font Awesome icon metadata.
   *
   * @param mixed $faVersion
   *   '5' for Font Awesome 5 metadata (optional).
   *
   * @return array
   *   The Font Awesome icon metadata.
   */
  public function getFontAwesomeIcons($faVersion);

  /**
   * Gets all available Font Awesome styles.
   *
   * @return array
   *   All the available Font Awesome styles.
   */
  public function getFontAwesomeStyles();

  /**
   * Converts a parameter to a valid Font Awesome version ('5' or '6').
   *
   * @param mixed $value
   *   A parameter to evaluate.
   *
   * @return string
   *   '5' or '6' as a valid Font Awesome version (defaults to 6).
   */
  public function toValidFontAwesomeVersion($value);

}
