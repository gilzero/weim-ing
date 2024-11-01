<?php

namespace Drupal\xray_audit\Services;

/**
 * Retrieve data about Navigation Architecture.
 */
interface NavigationArchitectureInterface {

  /**
   * Get menu architecture.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   The menu architecture object.
   */
  public function getMenuArchitecture(string $menu_name): array;

  /**
   * Get menus.
   *
   * @return array
   *   Menus.
   */
  public function getMenuList(): array;

}
