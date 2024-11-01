<?php

namespace Drupal\xray_audit\Services;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Retrieve data about Navigation Architecture.
 */
class NavigationArchitecture implements NavigationArchitectureInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $rendererService;

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Construct service Extractor Display Modes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Service Entity Type Manager.
   * @param \Drupal\Core\Render\RendererInterface $rendererService
   *   Drupal renderer service.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   Service "menu.link_tree".
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   Service "menu.link_manager".
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $rendererService, MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->rendererService = $rendererService;
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuArchitecture(string $menu_name): array {
    $parameters = new MenuTreeParameters();
    $menu_tree = $this->menuLinkTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $menu_tree = $this->menuLinkTree->transform($menu_tree, $manipulators);

    // Instance storage.
    $storage = $this->instanceStorage();

    foreach ($menu_tree as $menu_tree_element) {
      $this->processMenuTreeElement($menu_tree_element, $storage);
    }
    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuList(): array {
    $excluded_menus = ['admin'];
    $menus = [];

    $menu_objects = $this->entityTypeManager->getStorage('menu')->loadMultiple();
    foreach ($menu_objects as $menu_object) {
      if (in_array($menu_object->id(), $excluded_menus)) {
        continue;
      }
      $menus[$menu_object->id()] = $menu_object->label();
    }
    return $menus;
  }

  /**
   * Instance storage.
   *
   * @return array
   *   Storage.
   */
  protected function instanceStorage(): array {
    return [
      'items' => [],
      'item_number' => 0,
      'level_max' => 0,
    ];
  }

  /**
   * Process menu tree element.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $menu_tree_element
   *   Menu tree element.
   * @param array $storage
   *   Storage.
   * @param array $levels
   *   Un array where to storage all levels title.
   */
  protected function processMenuTreeElement(MenuLinkTreeElement $menu_tree_element, array &$storage, array $levels = []) {
    $menu_item = [];

    // Count of items.
    $storage['item_number']++;

    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $menu_tree_element->link;

    // Set max level.
    if ($menu_tree_element->depth > $storage['level_max']) {
      $storage['level_max'] = $menu_tree_element->depth;
    }

    $link_object = $link->getUrlObject();
    $link_object->setAbsolute();

    // Link to edit the menu item.
    $edit_link_object = $link->getEditRoute();
    $edit_link = ($edit_link_object instanceof Url) ? $edit_link_object->setAbsolute() : '';

    // Get the menu item data.
    $menu_item['level'] = $menu_tree_element->depth;
    $menu_item['title'] = $link->getTitle();
    $menu_item['link'] = empty($link_object->toString()) ? '' : $link_object;
    $menu_item['enabled'] = $link->isEnabled();
    $menu_item['parent'] = $link->getParent();
    $menu_item['route_name'] = $link->getRouteName();
    $menu_item['route_parameters'] = $link->getRouteParameters();
    $menu_item['edit_link'] = $edit_link;

    // Get target.
    $this->processMenuItemTarget($menu_item);

    // Parents references.
    // When it is a first level item, we reset the parents.
    if ($menu_tree_element->depth === 1) {
      $levels = [];
    }
    $levels[$menu_tree_element->depth] = $menu_item['title'];
    $menu_item['levels'] = $levels;

    // Add menu item to storage.
    $storage['items'][] = $menu_item;
    unset($menu_item);

    // Process children.
    if ($menu_tree_element->hasChildren) {
      foreach ($menu_tree_element->subtree as $child) {
        $this->processMenuTreeElement($child, $storage, $levels);
      }
    }
  }

  /**
   * Process menu item target.
   *
   * @param array $menu_item
   *   Menu item.
   */
  protected function processMenuItemTarget(array &$menu_item) {
    $menu_item['target'] = [];

    switch ($menu_item['route_name']) {
      case '':
        $menu_item['target']['route_name'] = $this->t('External link');
        break;

      case 'entity.node.canonical':
        if (isset($menu_item['route_parameters']['node'])) {
          $nid = $menu_item['route_parameters']['node'];
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
          if (!$node instanceof NodeInterface) {
            return;
          }
          $this->buildEntityTarget($menu_item, $node);
        }
        break;

      case 'entity.taxonomy_term.canonical':
        if (isset($menu_item['route_parameters']['taxonomy_term'])) {
          $tid = $menu_item['route_parameters']['taxonomy_term'];
          $taxonomy_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
          if (!$taxonomy_term instanceof TermInterface) {
            return;
          }
          $this->buildEntityTarget($menu_item, $taxonomy_term);
        }
        break;

      default:
        $menu_item['target']['route_name'] = $menu_item['route_name'] === '<nolink>' ? '' : $menu_item['route_name'];
    }
  }

  /**
   * Build entity target.
   *
   * @param array $menu_item
   *   Menu item.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity.
   */
  protected function buildEntityTarget(&$menu_item, ContentEntityInterface $entity) {
    unset($menu_item['target']['route_name']);
    $menu_item['target']['type'] = $entity->getEntityTypeId() . ': ';
    $menu_item['target']['content_type'] = $entity->bundle() . ',';
    $menu_item['target']['id'] = 'id: ' . $entity->id();
  }

}
