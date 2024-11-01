<?php

namespace Drupal\iconify_icons;

/**
 * Interface for Iconify service.
 */
interface IconifyServiceInterface {

  /**
   * Gets icons from query.
   *
   * @param string $query
   *   The search query.
   * @param string $collection
   *   The icon set.
   * @param int $limit
   *   (Optional) The maximum number of icons to retrieve. Defaults to 10.
   *
   * @return array
   *   The array of icons.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function getIcons(string $query, string $collection, int $limit = 10): array;

  /**
   * Gets collections from Iconify API.
   *
   * @return array
   *   The array of collections.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function getCollections(): array;

  /**
   * Gets a set of icons from a specific collection.
   *
   * @param string $collection
   *   The collection name.
   *
   * @return array
   *   The array with a set of icons by collection.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function getIconsByCollection(string $collection): array;

  /**
   * Generates SVG icon.
   *
   * @param string $collection
   *   The collection name.
   * @param string $icon_name
   *   The icon name.
   * @param array $parameters
   *   The icon parameters (optional).
   *
   * @return string
   *   The svg icon.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function generateSvgIcon(string $collection, string $icon_name, array $parameters = []): string;

  /**
   * Generates SVG icons.
   *
   * @param array $icons
   *   All the icons that belongs to the same collection.
   * @param array $parameters
   *   The icon parameters (optional).
   *
   * @return array
   *   The svg icons array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function generateSvgIcons(array $icons, array $parameters = []): array;

  /**
   * Gets icon source.
   *
   * @param string $collection
   *   The collection name.
   * @param string $icon_name
   *   The icon name.
   * @param array $parameters
   *   The icon parameters (optional).
   *
   * @return string
   *   The icon source.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function getIconSource(string $collection, string $icon_name, array $parameters = []): string;

  /**
   * Gets iconify icons API version.
   *
   * @return string
   *   The iconify icons API version.
   */
  public function getApiVersion(): string;

}
