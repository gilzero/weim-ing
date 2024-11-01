<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu skins utility methods.
 */
class UltimenuSkin extends UltimenuBase implements UltimenuSkinInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Static cache for the skin path.
   *
   * @var array
   */
  protected $skinPath;

  /**
   * Static cache of skins.
   *
   * @var array
   */
  protected $skins;

  /**
   * Static cache of libraries.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey = 'ultimenu';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->setFileSystem($container->get('file_system'));
    $instance->setThemeHandler($container->get('theme_handlerl'));
    return $instance;
  }

  /**
   * Sets theme handler service.
   */
  public function setThemeHandler(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
    return $this;
  }

  /**
   * Sets file system service.
   */
  public function setFileSystem(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkinPath($uri) {
    if (!isset($this->skinPath[md5($uri)])) {
      [, $skin_name] = array_pad(array_map('trim', explode("|", $uri, 2)), 2, NULL);

      if ($skin_name) {
        if (strpos($uri, "module|") !== FALSE) {
          $format = 'css/theme/%s.css';
          $skin_path = sprintf($format, $skin_name);
        }
        elseif (strpos($uri, "custom|") !== FALSE) {
          if ($path = $this->getSetting('skins')) {
            $format = '/%s/%s.css';
            $skin_path = sprintf($format, $path, $skin_name);
          }
        }
        elseif (strpos($uri, "theme|") !== FALSE) {
          $theme_default = $this->config('default', 'system.theme');
          $path = $this->getPath('theme', $theme_default) . '/css/ultimenu';
          if ($path && is_dir($path)) {
            $format = '/%s/%s.css';
            $skin_path = sprintf($format, $path, $skin_name);
          }
        }
      }

      $this->skinPath[md5($uri)] = $skin_path ?? '';
    }
    return $this->skinPath[md5($uri)];
  }

  /**
   * {@inheritdoc}
   */
  public function getName($path) {
    $skin_name     = $this->fileSystem->basename($path, '.css');
    $skin_basename = str_replace("ultimenu--", "", $skin_name);

    return str_replace("-", "_", $skin_basename);
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins() {
    if (!isset($this->skins)) {
      $cache = $this->cache->get($this->cacheKey . ':skin');

      if ($cache && $data = $cache->data) {
        $this->skins = $data;
      }
      else {
        $theme_default = $this->config('default', 'system.theme');
        $theme_skin    = $this->getPath('theme', $theme_default) . '/css/ultimenu';
        $custom_skin   = trim($this->getSetting('skins') ?: '');
        $module_skin   = $this->getPath('module', 'ultimenu') . '/css/theme';
        $mask          = '/.css$/';
        $files         = $skins = [];

        if (is_dir($module_skin)) {
          foreach ($this->fileSystem->scanDirectory($module_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if ($custom_skin && is_dir($custom_skin)) {
          foreach ($this->fileSystem->scanDirectory($custom_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if (is_dir($theme_skin)) {
          foreach ($this->fileSystem->scanDirectory($theme_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if ($files) {
          foreach ($files as $file) {
            $uri = $file->uri;
            $name = $file->name;

            // Simplify lengthy deep directory structure.
            if (strpos($uri, $module_skin) !== FALSE) {
              $uri = "module|" . $name;
            }
            // Fix for Warning: Empty needle.
            elseif ($custom_skin && strpos($uri, $custom_skin) !== FALSE) {
              $uri = "custom|" . $name;
            }
            elseif (strpos($uri, $theme_skin) !== FALSE) {
              $uri = "theme|" . $name;
            }

            // Convert file name to CSS friendly for option label and styling.
            $skins[$uri] = Html::cleanCssIdentifier(mb_strtolower($name));
          }

          ksort($skins);
          $this->cache->set($this->cacheKey . ':skin', $skins, Cache::PERMANENT, ['skin']);
        }
        $this->skins = $skins;
      }
    }
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions($all = FALSE) {
    // Invalidate the theme cache to update ultimenu region-based theme.
    $this->themeHandler->refreshInfo();

    if ($all) {
      // Clear the skins cache.
      $this->skins = NULL;
      // Invalidate the block cache to update ultimenu-based derivatives.
      /* @phpstan-ignore-next-line */
      // @todo recheck $this->blockManager()->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCaretSkins(): array {
    return [
      'plus',
      'triangle',
      'arrow',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasSkins(): array {
    return [
      'bottomsheet',
      'pushdown',
      'scalein',
      'slidein',
      'slidein-oldies',
      'slideover',
      'zoomin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoBuild(): array {
    if (!isset($this->libraryInfoBuild)) {
      $common = ['version' => \Drupal::VERSION];
      $libraries = [];
      foreach ($this->getSkins() as $key => $skin) {
        $skin_css_path = $this->getSkinPath($key);
        $skin_basename = $this->getName($skin_css_path);

        $libraries['skin.' . $skin_basename] = [
          'css' => [
            'theme' => [
              $skin_css_path => [],
            ],
          ],
        ];
      }

      foreach ($this->getCaretSkins() as $skin) {
        $id = 'caret.' . $skin;
        $libraries[$id] = [
          'css' => [
            'theme' => [
              'css/components/caret/ultimenu--caret-' . $skin . '.css' => [],
            ],
          ],
        ];
      }

      foreach ($this->getOffCanvasSkins() as $skin) {
        $libraries['offcanvas.' . $skin] = [
          'css' => [
            'theme' => [
              'css/components/offcanvas/ultimenu--offcanvas-' . $skin . '.css' => [],
            ],
          ],
        ];
      }

      $libraries['olivero'] = [
        'css' => [
          'theme' => [
            'css/components/ultimenu.olivero.css' => [],
          ],
        ],
      ];

      foreach ($libraries as &$library) {
        $library += $common;
        $library['dependencies'][] = 'ultimenu/hamburger';
      }

      $this->libraryInfoBuild = $libraries;
    }
    return $this->libraryInfoBuild;
  }

}
