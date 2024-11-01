<?php

namespace Drupal\type_tray\Controller;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Controller\NodeController;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tweaks NodeController according to our needs.
 */
class TypeTrayController extends NodeController {

  /**
   * The module-relative path of the thumbnail to be used by default.
   */
  public const TYPE_TRAY_DEFAULT_THUMBNAIL_PATH = '/assets/thumbnails/wysiwyg1.png';

  /**
   * The module-relative path of the icon to be used by default.
   */
  public const TYPE_TRAY_DEFAULT_ICON_PATH = '/assets/icons/file-text.svg';

  /**
   * The "Uncategorized" fall-back category label.
   */
  public const UNCATEGORIZED_LABEL = 'Uncategorized';

  /**
   * The key to be used for the fall-back category.
   */
  public const UNCATEGORIZED_KEY = '_none';

  /**
   * The key to be used for the "Favorites" category, if applicable.
   */
  public const FAVORITES_KEY = 'type_tray__favorites';

  /**
   * The label to be used for the "Favorites" category, if applicable.
   */
  public const FAVORITES_LABEL = 'Favorites';

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->cacheTagsInvalidator = $container->get('cache_tags.invalidator');
    $instance->moduleList = $container->get('extension.list.module');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * Override the addPage so we are able to display it our way.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array for a list of node types that can be added.
   */
  public function addPage(Request $request = NULL) {
    $config = $this->config('type_tray.settings');
    if ($request) {
      $layout = $request->query->get('layout') ?? 'grid';
    }
    else {
      $layout = 'grid';
    }
    $build = [
      '#theme' => 'type_tray_page',
      '#items' => [],
      '#layout' => $layout,
      '#category_labels' => static::getTypeTrayCategories(),
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
      '#attached' => [
        'library' => 'type_tray/type_tray',
      ],
    ];

    // Only use node types the user has access to.
    $types = [];
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $types[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Group the types by their categories.
    $tmp_types = [];
    // If there is at least one favorite for this user, create a favorites
    // category as the first group.
    $user_favorites = static::getUserFavorites();
    if (!empty($user_favorites)) {
      $favorites = array_map(function ($item) use ($types) {
        return $types[$item];
      }, array_combine($user_favorites, $user_favorites));
      $tmp_types[static::FAVORITES_KEY] = $favorites;
    }
    foreach ($types as $type_id => $type) {
      $category = static::getCategory($type->id());
      $tmp_types[key($category)][] = $type;
    }
    // We will honor the order categories were entered in the config form, and
    // make sure "Uncategorized" comes after all of them.
    $grouped_types = [];
    foreach (static::getTypeTrayCategories() as $category_key => $category_label) {
      if (!empty($tmp_types[$category_key])) {
        $grouped_types[$category_key] = $tmp_types[$category_key];
      }
    }
    if (!empty($grouped_types[static::UNCATEGORIZED_KEY])) {
      $uncategorized_group = $grouped_types[static::UNCATEGORIZED_KEY];
      unset($grouped_types[static::UNCATEGORIZED_KEY]);
      $grouped_types[static::UNCATEGORIZED_KEY] = $uncategorized_group;
    }

    foreach ($grouped_types as $category => $group) {
      // Sort them within each group.
      $group = $this->sortTypesByWeight($group);

      // Add the additional info the build.
      foreach ($group as $type) {
        assert($type instanceof NodeType);
        $settings = $type->getThirdPartySettings('type_tray');
        $short_description = [
          '#markup' => $type->getDescription(),
        ];
        $extended_description = [];
        if (!empty($settings['type_description'])) {
          $text_format = $config->get('text_format') ?? 'plain_text';
          $extended_description = [
            '#type' => 'processed_text',
            '#format' => $text_format,
            '#text' => $settings['type_description'],
          ];
        }

        // Prepare a link to add/remove to/from favorites.
        if (in_array($type->id(), $user_favorites)) {
          $favorite_link_text = $this->t('Remove @type from favorites', [
            '@type' => $type->label(),
          ]);
          $favorite_link_url = Url::fromRoute('type_tray.favorites', [
            'type' => $type->id(),
            'op' => 'remove',
          ])->toString();
          $favorite_link_action = 'remove';
        }
        else {
          $favorite_link_text = $this->t('Add @type to favorites', [
            '@type' => $type->label(),
          ]);
          $favorite_link_url = Url::fromRoute('type_tray.favorites', [
            'type' => $type->id(),
            'op' => 'add',
          ])->toString();
          $favorite_link_action = 'add';
        }

        $thumbnail_url = !empty($settings['type_thumbnail']) ? $settings['type_thumbnail'] : $this->moduleList->getPath('type_tray') . static::TYPE_TRAY_DEFAULT_THUMBNAIL_PATH;
        $icon_url = !empty($settings['type_icon']) ? $settings['type_icon'] : $this->moduleList->getPath('type_tray') . static::TYPE_TRAY_DEFAULT_ICON_PATH;

        $build['#items'][$category][$type->id()] = [
          '#theme' => 'type_tray_teaser',
          '#content_type_link' => Link::createFromRoute($type->label(), 'node.add', ['node_type' => $type->id()]),
          '#thumbnail_url' => $this->fileUrlGenerator->generateString($thumbnail_url),
          '#thumbnail_alt' => $this->t('Thumbnail of a @label content type.', [
            '@label' => $type->label(),
          ]),
          '#icon_url' => $this->fileUrlGenerator->generateString($icon_url),
          '#icon_alt' => $this->t('Icon of a @label content type.', [
            '@label' => $type->label(),
          ]),
          '#short_description' => $short_description,
          '#extended_description' => $extended_description,
          '#layout' => $layout,
          '#content_type_entity' => $type,
          '#favorite_link_text' => $favorite_link_text,
          '#favorite_link_url' => $favorite_link_url,
          '#favorite_link_action' => $favorite_link_action,
        ];
        if (!empty($settings['existing_nodes_link_text'])) {
          // This avoids having site builders rely on Config Translation to
          // make this translatable. Not ideal, but worth in this case.
          // @codingStandardsIgnoreLine
          $all_nodes_label = $this->t($settings['existing_nodes_link_text']);
          $admin_content_url = Url::fromRoute('system.admin_content')->toString();
          $all_nodes_url = Url::fromUserInput($admin_content_url, ['query' => ['type' => $type->id()]]);
          $build['#items'][$category][$type->id()]['#nodes_by_type_link'] = Link::fromTextAndUrl($all_nodes_label, $all_nodes_url);
        }

        CacheableMetadata::createFromObject($type)
          ->addCacheContexts(['user'])
          ->applyTo($build['#items'][$category][$type->id()]);
      }
    }

    return $build;
  }

  /**
   * Helper to check the weight on each type and sort by them.
   *
   * @param \Drupal\node\Entity\NodeType[] $types
   *   An indexed array of node types to sort. Note that if an associative
   *   array is passed in, the keys will be lost.
   *
   * @return \Drupal\node\Entity\NodeType[]
   *   The same array passed in, but sorted by the 'type_weight' third-party
   *   setting. Will use weight=0 if no value is defined for this setting.
   */
  private function sortTypesByWeight(array $types) {
    $items = [];
    foreach ($types as $type) {
      assert($type instanceof NodeType);
      $items[] = [
        'type' => $type,
        'weight' => $type->getThirdPartySetting('type_tray', 'type_weight', 0),
      ];
    }
    uasort($items, [SortArray::class, 'sortByWeightElement']);
    return array_map(function ($item) {
      return $item['type'];
    }, $items);
  }

  /**
   * Returns categories used to classify and group content types.
   *
   * @return array|string
   *   An associative array where keys are category names, and values their
   *   user-facing labels.
   */
  public static function getTypeTrayCategories() {
    $config = \Drupal::config('type_tray.settings');
    $fallback_label = $config->get('fallback_label') ?? static::UNCATEGORIZED_LABEL;
    $categories = $config->get('categories') ?? [];
    // If there is at least one type marked as favorite for this user, add it
    // as an available category.
    $user_favorites = static::getUserFavorites();
    if (!empty($user_favorites)) {
      $categories = [static::FAVORITES_KEY => static::FAVORITES_LABEL] + $categories;
    }
    // Add the fallback, and pass all labels through t() to make them available
    // to be translated through interface translation.
    $categories = $categories + [static::UNCATEGORIZED_KEY => $fallback_label];
    foreach ($categories as $key => &$category_label) {
      // @codingStandardsIgnoreLine
      $category_label = t($category_label);
    }
    return $categories;
  }

  /**
   * Helper to retrieve the types marked as favorites by the current user.
   *
   * @return string[]
   *   A list of type machine names that the current user marked as favorites.
   */
  public static function getUserFavorites() {
    $collection = \Drupal::keyValue('type_tray_favorites');
    $user_favorites = $collection->get(\Drupal::currentUser()->id()) ?? [];
    return array_keys(array_filter($user_favorites));
  }

  /**
   * Get the category key/label for a given content type.
   *
   * @param string $type_id
   *   The content type machine name.
   *
   * @return array
   *   A single-item associative array, where the key is the category key, and
   *   the value is the user-facing category label.
   */
  public static function getCategory($type_id) {
    $categories = static::getTypeTrayCategories();
    $uncategorized = [static::UNCATEGORIZED_KEY => $categories[static::UNCATEGORIZED_KEY]];

    /** @var \Drupal\node\Entity\NodeType $type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load($type_id);
    if (empty($type)) {
      return $uncategorized;
    }

    // Check if there's one chosen by an admin.
    $category = $type->getThirdPartySetting('type_tray', 'type_category');
    if (empty($category) || empty($categories[$category])) {
      return $uncategorized;
    }

    return [$category => $categories[$category]];
  }

  /**
   * Callback to add/remove a type from a user's favorites list.
   *
   * @param string $type
   *   The machine name of the content type being (un)favorited.
   * @param string $op
   *   Either 'add' or 'remove', indicating to add or remove the type from
   *   the favorites list.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function processFavorites($type, $op, Request $request) {
    if (!in_array($op, ['add', 'remove'], TRUE)) {
      throw new NotFoundHttpException();
    }

    // Only allow dealing with types that exist and this user can access.
    $type = $this->entityTypeManager()->getStorage('node_type')->load($type);
    if (empty($type)) {
      throw new NotFoundHttpException();
    }
    $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
    if (!$access->isAllowed()) {
      throw new NotFoundHttpException();
    }

    $collection = $this->keyValue('type_tray_favorites');
    $uid = $this->currentUser()->id();
    $user_favorites = $collection->get($uid) ?? [];
    // The value in the collection is an associative array where keys are type
    // machine names, and values are a boolean indicating whether it has been
    // favorited or not.
    $user_favorites[$type->id()] = $op === 'add' ? TRUE : FALSE;
    $collection->set($uid, $user_favorites);
    $this->cacheTagsInvalidator->invalidateTags([
      'config:node_type_list',
    ]);

    // Send the user back to the Type Tray page.
    return new RedirectResponse(Url::fromRoute('node.add_page')->setAbsolute()->toString());
  }

}
