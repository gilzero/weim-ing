<?php

namespace Drupal\ultimenu;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Interface for Ultimenu manager.
 */
interface UltimenuManagerInterface extends UltimenuInterface, TrustedCallbackInterface {

  /**
   * Returns the tool service.
   */
  public function tool();

  /**
   * Returns the tool service.
   */
  public function tree();

  /**
   * Returns array of needed assets suitable for #attached property.
   *
   * @param array $config
   *   The config which determine what library to attach.
   *
   * @return array
   *   The supported libraries.
   */
  public function attach(array $config = []): array;

  /**
   * Returns the contents using theme_ultimenu().
   *
   * @param array $build
   *   The array containing: config.
   *
   * @return array
   *   The alterable and renderable array of Ultimenu items.
   */
  public function build(array $build): array;

  /**
   * Builds the Ultimenu outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element): array;

  /**
   * Build the menu to contain Ultimenu regions.
   *
   * Helper function for ::build().
   *
   * @param array $config
   *   The config available for the menu tree.
   * @param \Drupal\Core\Cache\CacheableMetadata $tree_access_cacheability
   *   Internal use only. The aggregated cacheability metadata for the access
   *   results across the entire tree. Used when rendering the root level.
   * @param \Drupal\Core\Cache\CacheableMetadata $tree_link_cacheability
   *   Internal use only. The aggregated cacheability metadata for the menu
   *   links across the entire tree. Used when rendering the root level.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   *
   * @throws \DomainException
   */
  public function buildMenuTree(
    array $config,
    CacheableMetadata &$tree_access_cacheability,
    CacheableMetadata &$tree_link_cacheability,
  ): array;

  /**
   * Build the Ultimenu item.
   *
   * @param object $data
   *   The data containing menu item.
   * @param array $active_trails
   *   The menu item active trail ids.
   * @param array $config
   *   The config available for the menu item.
   *
   * @return array
   *   An array of the ultimenu item.
   */
  public function buildMenuItem($data, array $active_trails, array $config): array;

  /**
   * Returns the block content idenfied by its entity ID.
   */
  public function getBlock($bid): array;

  /**
   * A helper function to generate a list of blocks from a specified region.
   *
   * @param string $region
   *   The string identifier for a Ultimenu region. e.g. "ultimenu_main_about".
   * @param array $config
   *   The config available for the menu tree.
   *
   * @return array
   *   The renderable array of blocks within the region.
   */
  public function getBlocksByRegion($region, array $config): array;

  /**
   * Returns the Ultimenu blocks.
   *
   * @return array
   *   The blocks.
   */
  public function getUltimenuBlocks(): array;

  /**
   * Returns the array of enabled Ultimenu regions based on enabled settings.
   *
   * @return array
   *   An array of enabled regions definition based on enabled menu items.
   */
  public function getEnabledRegions(): array;

  /**
   * The array of available Ultimenu regions based on enabled menu items.
   *
   * @return array
   *   An array of regions definition dependent on available menu items.
   */
  public function getRegions(): array;

  /**
   * The array of available Ultimenu regions per menu.
   */
  public function getRegionsByMenu($menu_name): array;

  /**
   * Returns the renderable ajax link.
   */
  public function buildAjaxLink(array $config): array;

  /**
   * Returns the renderable array region data.
   *
   * @param string $region
   *   The string identifier for a Ultimenu region. e.g. "ultimenu_main_about".
   * @param array $config
   *   The config available for the menu tree.
   *
   * @return array
   *   The region data.
   */
  public function buildFlyout($region, array $config): array;

  /**
   * Returns the flyout if available.
   */
  public function getFlyout($region, array &$config): array;

  /**
   * Returns the available menus, excluding some admin menus.
   *
   * @return array
   *   The menus.
   */
  public function getMenus(): array;

  /**
   * Implements hook_system_info_alter().
   */
  public function systemInfoAlter(&$info, Extension $file, $type): void;

  /**
   * Returns unwanted Ultimenu regions for removal from theme .info.yml.
   *
   * When a menu item disabled or deleted, relevant dynamic Ultimenu regions
   * removed from the system, but theme .info.yml has a copy stored there.
   * System will always keep and display the unwanted.
   * Either manual deletion from .info.yml, or a force removal if so configured.
   *
   * @return array
   *   The array of unwanted Ultimenu regions from theme .info.yml, or empty.
   */
  public function removeRegions(): array;

}
