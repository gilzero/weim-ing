<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\block\BlockInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu utility methods.
 */
class UltimenuTool implements UltimenuToolInterface {

  use UltimenuTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The alias repository service.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $aliasRepository;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Static cache for the theme regions.
   *
   * @var array
   */
  protected $themeRegions;

  /**
   * Constructs a Ultimenu object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, PathMatcherInterface $path_matcher, AliasRepositoryInterface $alias_repository, InfoParserInterface $info_parser, LanguageManagerInterface $language_manager, PhpTransliteration $transliteration) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->aliasRepository = $alias_repository;
    $this->infoParser = $info_parser;
    $this->languageManager = $language_manager;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('path.matcher'),
      $container->get('path_alias.repository'),
      $container->get('info_parser'),
      $container->get('language_manager'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPathMatcher() {
    return $this->pathMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortenedHash($key) {
    return substr(sha1($key), 0, 8);
  }

  /**
   * {@inheritdoc}
   */
  public function getShortenedUuid($key) {
    [, $uuid] = array_pad(array_map('trim', explode(":", $key, 2)), 2, NULL);
    $uuid = str_replace('.', '__', $uuid ?: $key);
    [$shortened_uuid] = array_pad(array_map('trim', explode("-", $uuid, 2)), 2, NULL);
    return $shortened_uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function truncateRegionKey($string, $max_length = self::MAX_LENGTH) {
    // Transliterate the string.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $transformed = $this->transliteration->transliterate($string, $langcode);

    // Decode it.
    $transformed = Html::decodeEntities($transformed);
    $transformed = mb_strtolower(str_replace(['menu-', '-menu'], '', $transformed));
    $transformed = preg_replace('/[\W\s]+/', '_', $transformed);

    // Trim trailing underscores.
    $transformed = trim($transformed, '_');
    $transformed = Unicode::truncate($transformed, $max_length, TRUE, FALSE);
    return $transformed;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionKey($link, $max_length = self::MAX_LENGTH) {
    $menu_name = $link->getMenuName();
    $key = $link->getPluginId();
    $title = $this->getTitle($link);
    $goodies = $this->getSetting('goodies');
    $is_mlid = $goodies['ultimenu-mlid'] ?? FALSE;
    $is_hash = $goodies['ultimenu-mlid-hash'] ?? FALSE;
    $menu_name = $this->truncateRegionKey($menu_name, $max_length);

    if ($is_hash) {
      $menu_item = $this->getShortenedHash($key);
    }
    elseif ($is_mlid) {
      $menu_item = $this->getShortenedUuid($key);
    }
    else {
      $menu_item = $this->truncateRegionKey(trim($title), $max_length);
    }

    return 'ultimenu_' . $menu_name . '_' . $menu_item;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($link) {
    return $this->extractTitle($link)['title'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function extractTitleHtml($link) {
    $icon = '';
    $goodies = $this->getSetting('goodies');
    $titles = $this->extractTitle($link);
    $title_html = $title = $titles['title'];
    $custom_class = trim($this->getSetting('icon_class') ?: '');

    if ($custom_class) {
      $custom_class = Html::escape(strip_tags($custom_class));
    }

    if ($_icon = $titles['icon'] ?? NULL) {
      $is_fa = $titles['fa'] ?? FALSE;
      if ($custom_class) {
        $_icon = $custom_class . ' ' . $_icon;
      }
      $icon_class = $is_fa ? 'fa ' . $_icon : 'icon ' . $_icon;
      $icon = '<span class="ultimenu__icon ' . $icon_class . '" aria-hidden="true"></span>';
    }

    if (!empty($goodies['menu-desc']) && $description = $titles['desc'] ?? NULL) {
      // Render description, if so configured.
      $description = '<small>' . $description . '</small>';
      $title_html = empty($goodies['desc-top']) ? $title . $description : $description . $title;
    }

    // Holds the title in a separate SPAN for easy positioning if it has icon.
    if ($icon) {
      $title_html = $icon . '<span class="ultimenu__title">' . $title_html . '</span>';
    }

    return [
      'title' => $title,
      'title_html' => $title_html,
      'icon' => !empty($icon),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function parseThemeInfo(array $ultimenu_regions = []) {
    if (!isset($this->themeRegions)) {
      $theme = $this->getThemeDefault();
      $file = Ultimenu::getPath('theme', $theme) . '/' . $theme . '.info.yml';

      // Parse theme .info.yml file.
      $info = $this->infoParser->parse($file);

      $regions = [];
      foreach ($info['regions'] as $key => $region) {
        if (array_key_exists($key, $ultimenu_regions)) {
          $regions[$key] = $region;
        }
      }

      $this->themeRegions = $regions;
    }
    return $this->themeRegions;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedBlock(BlockInterface $block, array $config) {
    $access = $block->access('view', $this->currentUser, TRUE);
    $allowed = $access->isAllowed();

    // If not allowed, checks block visibility by paths and roles.
    // Ensures we are on the same page before checking visibility by roles.
    if (!$allowed && $this->isPageMatch($block, $config)) {
      // If we have visibility by roles, still restrict access accordingly.
      if ($roles = $this->getAllowedRoles($block)) {
        $allowed = $this->isAllowedByRole($block, $roles);
      }
      else {
        // Assumes visibility by paths in the least.
        $allowed = $this->isPageMatch($block, $config);
      }
    }
    return $allowed;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestPath(BlockInterface $block) {
    if ($visibility = $block->getVisibility()) {
      return empty($visibility['request_path']) ? FALSE : $visibility['request_path'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisiblePages(BlockInterface $block) {
    $pages = '';
    if ($request_path = $this->getRequestPath($block)) {
      $pages = empty($request_path['negate']) ? $request_path['pages'] : '';
    }
    return $pages;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedRoles(BlockInterface &$block) {
    if ($visibility_config = $block->getVisibility()) {
      if (isset($visibility_config['user_role'])) {
        return array_values($visibility_config['user_role']['roles']);
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedByRole(BlockInterface &$block, array $roles = []) {
    $current_user_roles = array_values($this->currentUser->getRoles());
    foreach ($current_user_roles as $role) {
      if (in_array($role, $roles)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isPageMatch(BlockInterface $block, array $config = []) {
    $page_match = FALSE;
    if ($pages = $this->getVisiblePages($block)) {
      $path = $config['current_path'];

      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if ($path_check = $this->aliasRepository->lookupByAlias($path, $langcode)) {
        if ($alias = $path_check['alias'] ?? NULL) {
          $path_alias = mb_strtolower($alias);
          $page_match = $this->pathMatcher->matchPath($path_alias, $pages);

          if ($path_alias != $path) {
            $page_match = $page_match || $this->pathMatcher->matchPath($path, $pages);
          }
        }
      }
    }

    return $page_match;
  }

  /**
   * Returns title with an icon class if available, e.g.: fa-mail|Contact us.
   */
  private function extractTitle($link): array {
    // Ever had a client which adds an empty space to a menu title? I did.
    $title = trim($link->getTitle() ?: '');
    $desc = trim($link->getDescription() ?: '');

    if ($desc) {
      $desc = strip_tags($desc, '<em><strong><i><b>');
      $desc = Xss::filter($desc);
    }

    if ($title) {
      $title = strip_tags($title);
      $title = Html::escape($title);
    }

    if ($result = $this->extractSource($title)) {
      return [
        'desc' => $desc,
        'fa' => $result['fa'],
        'icon' => $result['icon'],
        'title' => $result['text'],
      ];
    }
    elseif ($result = $this->extractSource($desc)) {
      return [
        'desc' => $result['text'],
        'fa' => $result['fa'],
        'icon' => $result['icon'],
        'title' => $title,
      ];
    }

    return ['desc' => $desc, 'title' => $title, 'fa' => FALSE];
  }

  /**
   * Extracts icon from a source: title or description.
   */
  private function extractSource($source): array {
    if ($source) {
      $is_icon = substr($source, 0, 5) === 'icon-';
      $is_fa = substr($source, 0, 3) === 'fa-';
      $iconized = $is_icon || $is_fa;
      $text = '';

      if ($iconized) {
        if (strpos($source, '|') !== FALSE) {
          [$icon_class, $text] = array_pad(array_map('trim', explode("|", $source, 2)), 2, NULL);
        }
        else {
          $icon_class = $source;
        }

        return [
          'text' => $text,
          'icon' => Html::cleanCssIdentifier($icon_class),
          'fa' => $is_fa,
        ];
      }
    }

    return [];
  }

}
