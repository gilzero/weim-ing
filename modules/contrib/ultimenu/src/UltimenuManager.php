<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu Manager implementation.
 */
class UltimenuManager extends UltimenuBase implements UltimenuManagerInterface {

  /**
   * Static cache for the menu blocks.
   *
   * @var array
   */
  protected $menuBlocks;

  /**
   * Static cache for the blocks.
   *
   * @var array
   */
  protected $blocks;

  /**
   * Static cache for the regions.
   *
   * @var array
   */
  protected $regions;

  /**
   * Static cache for the enabled regions.
   *
   * @var array
   */
  protected $enabledRegions;

  /**
   * Static cache for the enabled regions filtered by menu.
   *
   * @var array
   */
  protected $regionsByMenu;

  /**
   * Static cache for the menu options.
   *
   * @var array
   */
  protected $menuOptions;

  /**
   * Static cache for the offcanvas block.
   *
   * @var array
   */
  protected $block;

  /**
   * The Ultimenu tree service.
   *
   * @var \Drupal\ultimenu\UltimenuTree
   */
  protected $tree;

  /**
   * The Ultimenu tool service.
   *
   * @var \Drupal\ultimenu\UltimenuTool
   */
  protected $tool;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->setTool($container->get('ultimenu.tool'));
    $instance->setTree($container->get('ultimenu.tree'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderBuild'];
  }

  /**
   * {@inheritdoc}
   */
  public function tool() {
    return $this->tool;
  }

  /**
   * {@inheritdoc}
   */
  public function tree() {
    return $this->tree;
  }

  /**
   * Sets menu tool service.
   */
  public function setTool(UltimenuToolInterface $tool) {
    $this->tool = $tool;
    return $this;
  }

  /**
   * Sets menu tree service.
   */
  public function setTree(UltimenuTreeInterface $tree) {
    $this->tree = $tree;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $config = []): array {
    $goodies = $this->getSetting('goodies');
    $main = $config['menu_name'] == 'main' && empty($goodies['decouple-main-menu']);
    $load = [];

    $load['library'][] = 'ultimenu/ultimenu';
    if (!empty($config['skin_basename'])) {
      $load['library'][] = 'ultimenu/skin.' . $config['skin_basename'];
    }
    if (!empty($config['orientation'])
      && strpos($config['orientation'], 'v') !== FALSE) {
      $load['library'][] = 'ultimenu/vertical';
    }
    if (!empty($config['ajaxify'])) {
      $load['library'][] = 'ultimenu/ajax';
    }
    if (empty($goodies['no-extras'])) {
      $load['library'][] = 'ultimenu/extras';
    }
    if ($caret = $config['caret_skin'] ?? NULL) {
      $load['library'][] = 'ultimenu/caret.' . $caret;
    }

    // Specific for main navigation, or other enabled offcanvases.
    // @todo remove $main anytime later.
    if ($main || !empty($config['offcanvas'])) {
      $canvas_skin = empty($config['canvas_skin']) ? 'scalein' : $config['canvas_skin'];

      $load['library'][] = 'ultimenu/hamburger';
      $load['library'][] = 'ultimenu/offcanvas.' . $canvas_skin;

      // Optional if using the provided configuration.
      if (!empty($config['canvas_off']) && !empty($config['canvas_on'])) {
        $js_config = [
          'canvasOff' => trim(strip_tags($config['canvas_off'])),
          'canvasOn' => trim(strip_tags($config['canvas_on'])),
        ];
        $load['drupalSettings']['ultimenu'] = $js_config;
      }

      if ($this->getThemeDefault() == 'olivero') {
        $load['library'][] = 'ultimenu/olivero';
      }
    }

    if ($mw = $this->getSetting('ajaxmw')) {
      $load['drupalSettings']['ultimenu']['ajaxmw'] = $mw;
    }

    $this->moduleHandler->alter('ultimenu_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build): array {
    $build = [
      '#theme'      => 'ultimenu',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderBuild']],
    ];

    $this->moduleHandler->alter('ultimenu_build', $build);
    return $build;
  }

  /**
   * Builds the Ultimenu outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element): array {
    $build = $element['#build'];
    $config = $build['config'];
    $goodies = $this->getSetting('goodies');

    unset($build, $element['#build']);

    $config['current_path'] = Url::fromRoute('<current>')->toString();
    $tree_access_cacheability = new CacheableMetadata();
    $tree_link_cacheability = new CacheableMetadata();
    $items = $this->buildMenuTree($config, $tree_access_cacheability, $tree_link_cacheability);

    // Apply the tree-wide gathered access cacheability metadata and link
    // cacheability metadata to the render array. This ensures that the
    // rendered menu is varied by the cache contexts that the access results
    // and (dynamic) links depended upon, and invalidated by the cache tags
    // that may change the values of the access results and links.
    $tree_cacheability = $tree_access_cacheability->merge($tree_link_cacheability);
    $tree_cacheability->applyTo($element);

    // Build the elements.
    $element['#config'] = $config;
    $element['#items'] = $items;
    $element['#attached'] = $this->attach($config);
    $element['#cache']['tags'][] = 'config:ultimenu.' . $config['menu_name'];

    // Build the hamburger button, only for the offcanvas navigations.
    $bid = $config['bid'] ?? 'x';

    // @todo remove $main anytime later.
    $main = $config['menu_name'] == 'main' && empty($goodies['decouple-main-menu']);
    if ($main || !empty($config['offcanvas'])) {
      $label = $this->t('Menu @label', [
        '@label' => str_replace('Ultimenu: ', '', $config['label']),
      ]);

      $button = '<button data-ultimenu-button="#' . $bid . '" class="button button--ultimenu button--ultiburger" aria-label="' . $label . '"><span class="bars">' . $label . '</span></button>';
      $element['#suffix'] = Markup::create($button);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMenuTree(
    array $config,
    CacheableMetadata &$tree_access_cacheability,
    CacheableMetadata &$tree_link_cacheability,
  ): array {
    $menu_name = $config['menu_name'];
    $active_trails = $this->tree->menuActiveTrail()->getActiveTrailIds($menu_name);
    $tree = $this->tree->loadMenuTree($menu_name);

    if (empty($tree)) {
      return [];
    }

    $ultimenu = [];
    $theme = $this->getThemeDefault();
    $config['context_disabled_regions'] = $disabled_regions = $this->contextDisabledRegions($theme);

    foreach ($tree as $data) {
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }

      if ($data->access !== NULL && !$data->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Gather the access cacheability of every item in the menu link tree,
      // including inaccessible items. This allows us to render cache the menu
      // tree, yet still automatically vary the rendered menu by the same cache
      // contexts that the access results vary by.
      // However, if $data->access is not an AccessResultInterface object, this
      // will still render the menu link, because this method does not want to
      // require access checking to be able to render a menu tree.
      if ($data->access instanceof AccessResultInterface) {
        $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($data->access));
      }

      // Gather the cacheability of every item in the menu link tree. Some links
      // may be dynamic: they may have a dynamic text (e.g. a "Hi, <user>" link
      // text, which would vary by 'user' cache context), or a dynamic route
      // name or route parameters.
      $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($link));

      // Only render accessible links.
      if ($data->access instanceof AccessResultInterface && !$data->access->isAllowed()) {
        continue;
      }

      $config['region'] = $region = $this->tool->getRegionKey($link);
      // Exclude regions disabled by Context.
      if (isset($disabled_regions[$region])) {
        continue;
      }

      $ultimenu[$link->getPluginId()] = $this->buildMenuItem($data, $active_trails, $config);
    }
    return $ultimenu;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMenuItem($data, array $active_trails, array $config): array {
    $goodies    = $this->getSetting('goodies');
    $link       = $data->link;
    $url        = $link->getUrlObject();
    $mlid       = $link->getPluginId();
    $titles     = $config['titles'] = $this->tool->extractTitleHtml($link);
    $title      = $titles['title'];
    $li_classes = $li_attributes = $li_options = [];
    $region     = $config['region'];
    $flyout     = [];

    // Must run after the title, modified, or not, the region depends on it.
    $config['has_submenu'] = !empty($config['submenu'])
      && $link->isExpanded() && $data->hasChildren;
    $config['is_ajax_region'] = FALSE;
    $config['is_active'] = array_key_exists($mlid, $active_trails);
    $config['title'] = $title;
    $config['mlid'] = $mlid;
    $config['has_flyout'] = FALSE;

    $li_options['title-class'] = $title;
    $li_options['mlid-hash-class'] = $this->tool->getShortenedHash($mlid);

    if (!empty($goodies['mlid-class'])) {
      $li_options['mlid-class'] = $link->getRouteName() == '<front>'
        ? 'front_page' : $this->tool->getShortenedUuid($mlid);
    }

    if ($url->isRouted()) {
      if ($config['is_active']) {
        $li_classes[] = 'is-active-trail';
      }

      // Front page has no active trail.
      if ($link->getRouteName() == '<front>') {
        // Intentionally on the second line to not hit it till required.
        if ($this->tool->getPathMatcher()->isFrontPage()) {
          $li_classes[] = 'is-active-trail';
        }
      }
    }

    // Flyout.
    $flyout = $this->getFlyout($region, $config);

    // Provides hints for AJAX.
    $orientation = $config['orientation'] ?: '';
    $orientation = 'is-' . str_replace('ultimenu--', '', $orientation);
    $flyout_attributes['class'] = ['ultimenu__flyout', $orientation];

    if (!empty($flyout)) {
      $config['has_flyout'] = TRUE;
      if ($config['is_ajax_region']) {
        $flyout_attributes['data-ultiajax-region'] = $region;
      }
    }

    // Add LI title class based on title if so configured.
    foreach ($li_options as $li_key => $li_value) {
      if (!empty($goodies[$li_key])) {
        $li_classes[] = Html::cleanCssIdentifier(mb_strtolower('uitem--' . str_replace('_', '-', $li_value)));
      }
    }

    // Add LI counter class based on counter if so configured.
    if (!empty($goodies['counter-class'])) {
      static $item_id = 0;
      $li_classes[] = 'uitem--' . (++$item_id);
    }

    // Handle list item class attributes.
    $li_attributes['class'] = array_merge(['ultimenu__item', 'uitem'], $li_classes);

    // Pass link to template.
    return [
      'link' => $this->linkElement($config, $data),
      'flyout' => $flyout,
      'attributes' => new Attribute($li_attributes),
      'flyout_attributes' => new Attribute($flyout_attributes),
      'config' => $config,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildAjaxLink(array $config): array {
    return [
      '#type' => 'link',
      '#title' => $this->getFallbackText(),
      '#attributes' => [
        'class' => [
          'ultimenu__ajax',
          'use-ajax',
        ],
        'rel' => 'nofollow',
        'id' => Html::getUniqueId('ultiajax-' . $this->tool->getShortenedHash($config['mlid'])),
      ],
      '#url' => Url::fromRoute(
        'ultimenu.ajax', [
          'mlid' => $config['mlid'],
          // @todo revert if any issue: 'cur' => $config['current_path'],
          'sub' => $config['has_submenu'] ? 1 : 0,
        ],
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFlyout($region, array &$config): array {
    $flyout = [];
    if ($regions = $this->getSetting('regions')) {
      if (!empty($regions[$region])) {

        // Simply display the flyout, if AJAX is disabled.
        if (empty($config['ajaxify'])) {
          $flyout = $this->buildFlyout($region, $config);
        }
        else {
          // We have a mix of (non-)ajaxified regions here.
          // Provides an AJAX link as a fallback and also the trigger.
          // No need to check whether the region is empty, or not, as otherwise
          // defeating the purpose of ajaxified regions, to gain performance.
          // The site builder should at least provide one accessible block
          // regardless of complex visibility by paths or roles. A trade off.
          $ajax_regions = array_filter($config['regions'] ?? []);
          $ajax = $ajax_regions && in_array($region, $ajax_regions);
          $config['is_ajax_region'] = $ajax;

          $flyout = $ajax
            ? $this->buildAjaxLink($config)
            : $this->buildFlyout($region, $config);
        }
      }
    }
    return $flyout;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFlyout($region, array $config): array {
    $build   = $content = [];
    $count   = 0;
    $pos     = $config['submenu_position'] ?? NULL;
    $reverse = FALSE;

    if (!empty($config['has_submenu'])) {
      $reverse = $pos == 'bottom';
      $content[] = $this->tree->loadSubMenuTree($config);
    }

    if ($blocks = $this->getBlocksByRegion($region, $config)) {
      $content[] = $blocks;
      $count = count($blocks);
    }

    if ($content = array_filter($content)) {
      $config['count']  = $count;
      $build['content'] = $reverse ? array_reverse($content, TRUE) : $content;
      $build['#config'] = $config;
      $build['#region'] = $region;
      $build['#sorted'] = TRUE;

      $attributes['class'][] = 'ultimenu__region';

      // Useful to calculate grids.
      if ($count) {
        $attributes['class'][] = 'region';
        $attributes['class'][] = 'region--count-' . $count;
      }

      // Add the region theme wrapper for the flyout.
      $build['#attributes'] = $attributes;
      $build['#theme_wrappers'][] = 'region';
    }
    return $build;
  }

  /**
   * Returns the block content idenfied by its entity ID.
   */
  public function getBlock($bid): array {
    if (!isset($this->block[$bid])) {
      $this->block[$bid] = [];
      if ($block = $this->load($bid, 'block')) {
        $this->block[$bid] = $block->getPlugin()->build();
      }
    }
    return $this->block[$bid];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksByRegion($region, array $config): array {
    if (!isset($this->blocks[$region])) {
      $build = [];
      $blocks = $this->loadByProperties([
        'theme' => $this->getThemeDefault(),
        'region' => $region,
      ], 'block');

      if ($blocks) {
        uasort($blocks, 'Drupal\block\Entity\Block::sort');

        // Only provides extra access checks if the region is ajaxified.
        if (empty($config['ajaxify'])) {
          foreach ($blocks as $key => $block) {
            if ($block->access('view')) {
              $build[$key] = $this->entityTypeManager->getViewBuilder($block->getEntityTypeId())->view($block, 'block');
            }
          }
        }
        else {
          foreach ($blocks as $key => $block) {
            if ($this->tool->isAllowedBlock($block, $config)) {
              $build[$key] = $this->entityTypeManager->getViewBuilder($block->getEntityTypeId())->view($block, 'block');
            }
          }
        }
      }

      // Merges with blocks provided by Context.
      if ($context_blocks = $this->contextBlocks($region, $build)) {
        $build = array_merge($build, $context_blocks);
      }

      $this->blocks[$region] = $build;
    }
    return $this->blocks[$region];
  }

  /**
   * {@inheritdoc}
   */
  public function getUltimenuBlocks(): array {
    if (!isset($this->menuBlocks)) {
      $this->menuBlocks = [];
      $blocks = $this->getSetting('blocks');
      foreach ($this->getMenus() as $delta => $nice_name) {
        if (!empty($blocks[$delta])) {
          $this->menuBlocks[$delta] = $this->t('@name', ['@name' => $nice_name]);
        }
      }
      asort($this->menuBlocks);
    }
    return $this->menuBlocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledRegions(): array {
    if (!isset($this->enabledRegions)) {
      $this->enabledRegions = [];
      $regions_all = $this->getRegions();

      // First limit to enabled regions from the settings.
      if (($regions_enabled = $this->getSetting('regions')) !== NULL) {
        foreach (array_filter($regions_enabled) as $enabled) {
          // We must depend on enabled menu items as always.
          // A disabled menu item will automatically drop its region.
          if (array_key_exists($enabled, $regions_all)) {
            $this->enabledRegions[$enabled] = $regions_all[$enabled];
          }
        }
      }
    }
    return $this->enabledRegions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions(): array {
    if (!isset($this->regions)) {
      $blocks      = $this->getSetting('blocks');
      $menu_blocks = is_array($blocks) ? array_filter($blocks) : [$blocks];
      $menus       = [];

      foreach ($menu_blocks as $delta => $title) {
        $menus[$delta] = $this->tree->loadMenuTree($delta);
      }

      $regions = [];
      foreach ($menus as $menu_name => $tree) {
        foreach ($tree as $item) {
          $name_id = $this->tool->truncateRegionKey($menu_name);
          $name_id_nice = str_replace("_", " ", $name_id);
          $link = $item->link;

          $menu_title = $this->tool->getTitle($link);
          $region_key = $this->tool->getRegionKey($link);
          $regions[$region_key] = "Ultimenu:$name_id_nice: $menu_title";
        }
      }
      $this->regions = $regions;
    }
    return $this->regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionsByMenu($menu_name): array {
    if (!isset($this->regionsByMenu[$menu_name])) {
      $regions = [];
      foreach ($this->getEnabledRegions() as $key => $region_name) {
        if (strpos($key, 'ultimenu_' . $menu_name . '_') === FALSE) {
          continue;
        }
        $regions[$key] = $region_name;
      }
      $this->regionsByMenu[$menu_name] = $regions;
    }
    return $this->regionsByMenu[$menu_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getMenus(): array {
    if (!isset($this->menuOptions)) {
      $menus = $this->loadMultiple('menu');
      $this->menuOptions = $this->tree->getMenus($menus);
    }
    return $this->menuOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    $ok = $file->getName() == $this->getThemeDefault();
    $goodies = $this->getSetting('goodies');

    // Make regions available for all themes, except admin to avoid headaches
    // during theme switching like at most devs.
    if (!empty($goodies['fe-themes'])) {
      $name = $info['name'] ?? '';
      $hidden = $info['hidden'] ?? FALSE;
      $desc = $info['description'] ?? 'blah';
      // Drupal has no keyword/ grouping to distinguish [front|back]-end themes.
      $admin = stripos($desc, 'admin') !== FALSE;
      $ok = !$hidden && !$admin && !in_array($name, ['Stark']);
    }

    if ($type == 'theme' && isset($info['regions']) && $ok) {
      if ($regions = $this->getEnabledRegions()) {

        // Append the Ultimenu regions into the theme defined regions.
        foreach ($regions as $key => $region) {
          $info['regions'] += [$key => $region];
        }

        // Remove unwanted Ultimenu regions from theme .info if so configured.
        if ($remove_regions = $this->removeRegions()) {
          foreach ($remove_regions as $key => $region) {
            unset($info['regions'][$key]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeRegions(): array {
    $goodies = $this->getSetting('goodies');
    if (empty($goodies['force-remove-region'])) {
      return [];
    }
    return $this->tool->parseThemeInfo($this->getRegions());
  }

  /**
   * Returns available blocks managed by Context.
   */
  private function contextBlocks($region, array $build): array {
    if ($context_manager = $this->service('context.manager')) {
      foreach ($context_manager->getActiveReactions('blocks') as $reaction) {
        $check = $reaction->execute($build);
        return $check[$region] ?? [];
      }
    }
    return [];
  }

  /**
   * Returns available regions disabled by Context.
   */
  private function contextDisabledRegions($theme): array {
    if ($context_manager = $this->service('context.manager')) {
      foreach ($context_manager->getActiveReactions('regions') as $reaction) {
        $check = $reaction->getConfiguration();
        if (isset($check['regions'])
          && $regions = ($check['regions'][$theme] ?? [])) {
          return array_combine($regions, $regions);
        }
      }
    }
    return [];
  }

  /**
   * Return the fallback text.
   */
  private function getFallbackText(): object {
    $text = strip_tags($this->getSetting('fallback_text'));
    $text = Html::escape($text);
    return $this->t('@text', ['@text' => $text ?: 'Loading... Click here if it takes longer.']);
  }

  /**
   * Providers a link element.
   */
  private function linkElement(array &$config, $data): array {
    $goodies  = $this->getSetting('goodies');
    $link     = $data->link;
    $url      = $link->getUrlObject();
    $titles   = $config['titles'];
    $title    = $titles['title_html'];
    $has_icon = $titles['icon'];
    $unlinks  = $config['unlinks'] ?? [];
    $region   = $config['region'];
    $options  = $link->getOptions();
    $unlink   = FALSE;

    if (!isset($options['attributes'])) {
      $options['attributes'] = [];
    }

    $attrs = &$options['attributes'];
    $classes = $attrs['class'] ?? [];

    // @todo remove, less likely since D7, but just in case.
    if ($classes && !is_array($classes)) {
      $classes = [$classes];
    }

    if ($url->isRouted()) {
      // Also enable set_active_class for the contained link.
      $options['set_active_class'] = TRUE;

      // Add a "data-drupal-link-system-path" attribute to let the
      // drupal.active-link library know the path in a standardized manner.
      $system_path = $url->getInternalPath();
      if (!$url->isExternal()) {
        // Special case for the front page.
        if ($url->getRouteName() === '<front>') {
          $system_path = '<front>';
        }
      }
      // @todo system path is deprecated - use the route name and parameters
      if ($system_path) {
        $attrs['data-drupal-link-system-path'] = $system_path;
        $config['system_path'] = $system_path;
      }
    }

    // Remove browser tooltip if so configured.
    if (!empty($goodies['no-tooltip'])) {
      unset($attrs['title']);
      $url->setOptions([
        'attributes' => ['title' => ''],
      ]);
    }

    // Add hint for external link.
    if ($url->isExternal()) {
      $classes[] = 'is-external';
    }

    if ($has_icon) {
      $classes[] = 'is-iconized';
    }

    if ($config['has_flyout']) {
      if ($config['is_ajax_region']) {
        $attrs['data-ultiajax-trigger'] = TRUE;
      }
      $title .= Ultimenu::CARET;
    }

    $attrs['class'] = $classes
      ? array_merge(['ultimenu__link'], $classes)
      : ['ultimenu__link'];

    // The HTML title to support icons.
    $markup = [
      '#markup' => $title,
      '#allowed_tags' => Ultimenu::TAGS,
    ];
    if (!$url->isExternal()) {
      // Respects core <nolink>.
      $nolink = $url->getRouteName() === '<nolink>';
    }
    else {
      $nolink = FALSE;
    }
    // Hijack if so required.
    if ($unlinks && $unlink = $config['unlink'] ?? NULL) {
      if ($region && in_array($region, $unlinks)) {
        $nolink = TRUE;
      }
    }

    // If provided, we still need to make it clickable, but not click through.
    $content = [];
    if ($nolink) {
      $attrs['class'][] = 'is-unlinked';

      if ($unlink) {
        // $attrs['class'][] = 'is-unlinked--' . $unlink;
        if ($unlink == 'nolink') {
          $content = $this->toHtml($markup, 'span', $attrs);
        }
        elseif ($unlink == 'hashed') {
          $attrs['href'] = '#';
          $content = $this->toHtml($markup, 'a', $attrs);
        }
      }
    }

    $options['attributes'] = $attrs;

    return $content ?: [
      '#type' => 'link',
      '#options' => $options,
      '#url' => $url,
      '#title' => $markup,
    ];
  }

}
