<?php

namespace Drupal\ultimenu;

/**
 * Interface for Ultimenu tools.
 */
interface UltimenuTreeInterface {

  /**
   * Returns the menu tree.
   */
  public function menuTree();

  /**
   * Returns the menu active trail.
   */
  public function menuActiveTrail();

  /**
   * Returns the menus.
   */
  public function getMenus(array $menus = []);

  /**
   * Returns a list of links based on the menu name.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   An array of the requested menu links.
   */
  public function loadMenuTree($menu_name);

  /**
   * Returns a list of submenu links based on the menu name.
   *
   * @param array $config
   *   The block config for this menu, containing menu_name, mlid, title, etc.
   *
   * @return array
   *   An array of the requested submenu links.
   */
  public function loadSubMenuTree(array $config);

}
