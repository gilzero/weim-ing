<?php

namespace Drupal\ultimenu;

use Drupal\block\BlockInterface;

/**
 * Interface for Ultimenu tools.
 */
interface UltimenuToolInterface {

  /**
   * Defines constant maz length for the region key.
   */
  const MAX_LENGTH = 28;

  /**
   * Returns path matcher service.
   */
  public function getPathMatcher();

  /**
   * Gets the shortened hash of a menu item key.
   *
   * @param string $key
   *   The menu item key.
   *
   * @return string
   *   The shortened hash.
   */
  public function getShortenedHash($key);

  /**
   * Gets the shortened UUID.
   *
   * @param string $key
   *   The menu item key with UUID.
   *
   * @return string
   *   The shortened UUID.
   */
  public function getShortenedUuid($key);

  /**
   * Simplify menu names or menu item titles for region key.
   *
   * If region key is to use menu item title:
   * Region key: ultimenu_LOOOOOOOOOOOONGMENUNAME_LOOOOOOOOOOOOOOOOOONGMENUITEM.
   * If region key is to use unfriendly key UUID, we'll only care for menu name.
   * Region key: ultimenu_LOOOOOOOOOOOOOONGMENUNAME_1c2d3e4.
   *
   * @param string $string
   *   The Menu name or menu item title.
   * @param int $max_length
   *   The amount of characters to truncate.
   *
   * @return string
   *   The truncated menu properties ready to use for region key.
   */
  public function truncateRegionKey($string, $max_length = self::MAX_LENGTH);

  /**
   * Gets the region key.
   *
   * @param object $link
   *   The menu item link object.
   * @param int $max_length
   *   The amount of characters to truncate.
   *
   * @return string
   *   The region key name based on shortened UUID, or menu item title.
   */
  public function getRegionKey($link, $max_length = self::MAX_LENGTH);

  /**
   * Returns title.
   */
  public function getTitle($link);

  /**
   * Returns titles as both HTML and plain text titles.
   */
  public function extractTitleHtml($link);

  /**
   * Checks if user has access to view a block, including its path visibility.
   */
  public function isAllowedBlock(BlockInterface $block, array $config);

  /**
   * Returns block visibility request path.
   */
  public function getRequestPath(BlockInterface $block);

  /**
   * Returns block visibility pages, only concerns if negate is empty.
   */
  public function getVisiblePages(BlockInterface $block);

  /**
   * Checks block visibility roles.
   */
  public function getAllowedRoles(BlockInterface &$block);

  /**
   * Checks if the user has access by defined roles.
   */
  public function isAllowedByRole(BlockInterface &$block, array $roles = []);

  /**
   * Checks if the visible pages match the current path.
   */
  public function isPageMatch(BlockInterface $block, array $config = []);

  /**
   * Returns the default theme Ultimenu regions from theme .info.yml.
   *
   * @param array $ultimenu_regions
   *   The ultimenu theme regions.
   *
   * @return array
   *   The Ultimenu regions.
   */
  public function parseThemeInfo(array $ultimenu_regions = []);

}
