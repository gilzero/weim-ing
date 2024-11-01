<?php

namespace Drupal\ui_patterns_settings\Plugin\UiPatterns\SettingDataProvider;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderBase;
use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderInterface;
use Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType\LinksSettingType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the ui_patterns_settings_data_provider.
 *
 * @UiPatternsSettingDataProvider(
 *   id = "menu",
 *   label = @Translation("Menu"),
 *   description = @Translation("Data Provider for menus."),
 *   settingType = "links"
 * )
 */
class MenuDataProvider extends PatternSettingDataProviderBase implements PatternSettingDataProviderInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The menu ID.
   *
   * @var string
   */
  protected $menuId;

  /**
   *
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
                       $plugin_id,
                       $plugin_definition
  ) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
    );
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);
    $plugin->routeMatch = $container->get('current_route_match');
    $plugin->menuLinkTree = $container->get('menu.link_tree');
    return $plugin;
  }

  /**
   * Get menus list.
   *
   * @return array
   *   List of menus.
   */
  private function getMenuList() {
    $all_menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
    $menus = [
      "" => "(None)",
    ];
    foreach ($all_menus as $id => $menu) {
      $menus[$id] = $menu->label();
    }
    asort($menus);
    return $menus;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($value) {
    $value = $value ?? [];
    $form = [];
    $form["menu"] = [
      '#type' => 'select',
      '#title' => 'Menu',
      '#options' => $this->getMenuList(),
      '#default_value' => \array_key_exists("menu", $value) ? $value["menu"] : "",
    ];
    $options = range(0, $this->menuLinkTree->maxDepth());
    unset($options[0]);
    $form['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => \array_key_exists("level", $value) ? $value["level"] : 1,
      '#options' => $options,
      '#required' => TRUE,
    ];
    $options[0] = $this->t('Unlimited');
    $form['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => \array_key_exists("depth", $value) ? $value["depth"] : 0,
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level and the final display is dependant of the pattern template.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($value) {
    if (!$value) {
      return [];
    }
    $this->menuId = $value["menu"];
    return $this->getMenuItems($value);
  }

  /**
   * Get menu items.
   *
   * @return array
   *   List of items.
   */
  private function getMenuItems($value): array {
    $level = (int) \array_key_exists("level", $value) ? $value["level"] : 1;
    $depth = (int) \array_key_exists("depth", $value) ? $value["depth"] : 0;
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth($level);

    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuLinkTree->maxDepth()));
    }

    $tree = $this->menuLinkTree->load($this->menuId, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree = $this->menuLinkTree->build($tree);
    if (\array_key_exists("#items", $tree)) {
      $variables = [
        "items" => $tree["#items"],
      ];
      $this->moduleHandler->invokeAll("preprocess_menu", [&$variables]);
      $variables["items"] = LinksSettingType::normalize($variables["items"]);
      return $variables["items"];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    if (!$this->menuId) {
      return;
    }
    $cache = $element["#cache"] ?? [];
    $element["#cache"] = array_merge($cache, [
      "tags" => ['config:system.menu.' . $this->menuId],
    ]);
  }

}
