<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu utility methods.
 */
class UltimenuTree implements UltimenuTreeInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a UltimenuTree object.
   */
  public function __construct(
    MenuActiveTrailInterface $menu_active_trail,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.active_trail'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function menuTree() {
    if (!isset($this->menuTree)) {
      $this->menuTree = Ultimenu::service('menu.link_tree');
    }
    return $this->menuTree;
  }

  /**
   * {@inheritdoc}
   */
  public function menuActiveTrail() {
    return $this->menuActiveTrail;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenus(array $menus = []) {
    $custom_menus = [];
    if ($menus) {
      foreach ($menus as $menu_name => $menu) {
        $custom_menus[$menu_name] = Html::escape($menu->label());
      }
    }

    $excluded_menus = [
      'admin' => $this->t('Administration'),
      'devel' => $this->t('Development'),
      'tools' => $this->t('Tools'),
    ];

    $options = array_diff_key($custom_menus, $excluded_menus);
    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMenuTree($menu_name) {
    $parameters = new MenuTreeParameters();
    $parameters->setTopLevelOnly()->onlyEnabledLinks();

    return $this->loadAndTransform($menu_name, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function loadSubMenuTree(array $config) {
    $build = [];
    $level = 1;
    $depth = 4;
    $menu_name = $config['menu_name'];
    $link_id = $config['mlid'];
    $title = $config['title'];
    $collapsible = $config['submenu_collapsible'] ?? FALSE;

    $parameters = $this->menuTree()->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setRoot($link_id)->excludeRoot()->onlyEnabledLinks();
    $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree()->maxDepth()));
    $tree = $this->loadAndTransform($menu_name, $parameters, FALSE);

    if ($tree) {
      $content = $this->menuTree()->build($tree);
      $css_name = Html::cleanCssIdentifier(mb_strtolower($menu_name . '-' . $title));
      $classes = ['ultimenusub', 'ultimenusub--' . $css_name];

      if (!empty($content['#items'])) {
        if ($collapsible) {
          $classes[] = 'ultimenusub--collapsible';
        }

        $this->modifyItems($content['#items'], $config);
      }

      $build['content'] = $content;
      $build['#attributes']['class'] = $classes;
      $build['#theme_wrappers'][] = 'container';
    }

    return $build;
  }

  /**
   * Modifies sub-menu items.
   */
  private function modifyItems(array &$items, array $config): void {
    $collapsible = $config['submenu_collapsible'] ?? FALSE;

    foreach ($items as &$item) {
      $li_classes = ['ultimenu__item'];

      // Might be removed by some module.
      if ($url = $item['url'] ?? NULL) {
        $url->setOptions([
          'attributes' =>
            ['class' => ['ultimenu__link']],
        ]);
      }

      if ($item['is_expanded']) {
        $subtitle = $item['title'];

        if ($collapsible) {
          $li_classes[] = 'is-uitem-collapsible';
          $subtitle .= Ultimenu::CARET;
        }

        $item['title'] = [
          '#markup' => $subtitle,
          '#allowed_tags' => Ultimenu::TAGS,
        ];

        $subitems = &$item['below'];
        if ($subitems) {
          $this->modifyItems($subitems, $config);
        }
      }

      // Ensures we have something in case overriden.
      $item['attributes']->addClass($li_classes);
    }
  }

  /**
   * Load and transform a menu tree.
   */
  private function loadAndTransform($menu_name, $parameters, $flatten = TRUE) {
    $tree = $this->menuTree()->load($menu_name, $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    if ($flatten) {
      $manipulators[] = [
        'callable' => 'menu.default_tree_manipulators:flatten',
      ];
    }

    if ($this->moduleHandler->moduleExists('menu_manipulator')) {
      $manipulators[] = ['callable' => 'menu_manipulator.menu_tree_manipulators:filterTreeByCurrentLanguage'];
    }

    return $this->menuTree()->transform($tree, $manipulators);
  }

}
