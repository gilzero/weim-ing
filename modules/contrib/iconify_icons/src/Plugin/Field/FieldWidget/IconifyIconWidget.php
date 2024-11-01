<?php

namespace Drupal\iconify_icons\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\iconify_icons\IconifyServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'iconify_icon' widget.
 *
 * @FieldWidget(
 *   id = "iconify_icon_widget",
 *   module = "iconify_icons",
 *   label = @Translation("Iconify Icon"),
 *   field_types = {
 *     "iconify_icon"
 *   }
 * )
 */
class IconifyIconWidget extends WidgetBase {

  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal Iconify service.
   *
   * @var \Drupal\iconify_icons\IconifyServiceInterface
   */
  protected $iconify;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $config_factory, IconifyServiceInterface $iconify, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
    $this->iconify = $iconify;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('iconify_icons.iconify_service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'collections' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Collections'),
      '#size' => 60,
      '#maxlength' => 128,
      '#placeholder' => $this->t('Filter collections'),
      '#attributes' => [
        'class' => ['iconify-icons-widget-checkboxes-filter'],
      ],
      '#attached' => [
        'library' => ['iconify_icons/default'],
      ],
    ];

    $element['collections'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSetting('collections'),
      '#description' => $this->t('Select the collections which are going to provide the set of icons. See @collectionIconsLink list. Leave empty to select all.', [
        '@collectionIconsLink' => Link::fromTextAndUrl($this->t('the Iconify icon collections'), Url::fromUri('https://icon-sets.iconify.design/', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#attributes' => [
        'class' => [
          'iconify-icons-widget-collections',
        ],
      ],
      '#attached' => [
        'library' => [
          'iconify_icons/default',
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $selected_collections = array_filter($this->getSetting('collections'));
    $collections_summary = !empty($selected_collections) ? implode(', ', $selected_collections) : 'All';

    return [
      $this->t('Collections: @collections', ['@collections' => $collections_summary]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    /** @var \Drupal\iconify_icons\Plugin\Field\FieldType\IconifyIcon $iconify_icon */
    $iconify_icon = $items[$delta];
    // Generate a unique key for the drupalSettings based on the delta.
    $settings_key = $items->getName() . '_' . $delta;
    // Get selected collections from widget settings.
    $selected_collections = $this->getSetting('collections');
    $collection = implode(',', array_filter($selected_collections, static fn($value) => $value !== 0));

    $icon = $iconify_icon->get('icon')->getValue() ?? '';
    // Extract the icon name and collection name using a regular expression.
    if ($iconDetails = $this->extractIconDetails($icon)) {
      [$icon_name, $icon_collection] = $iconDetails;
      $icon_svg = $this->iconify->generateSvgIcon($icon_collection, $icon_name);
    }

    $element['icon'] = [
      '#type' => 'iconify_icons',
      '#title' => $cardinality === 1 ? $this->fieldDefinition->getLabel() : $this->t('Icon'),
      '#default_value' => $iconify_icon->get('icon')->getValue(),
      '#required' => $element['#required'],
      '#size' => 50,
      '#attributes' => [
        'data-settings-key' => $settings_key,
      ],
      '#collections' => $collection,
      '#attached' => [
        'drupalSettings' => [
          'iconify_icons' => [
            $settings_key => [
              'icon_name' => $icon,
              'icon_svg' => $icon_svg ?? '',
            ],
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $collection = $this->getSetting('collections');
    foreach ($values as $delta => &$item) {
      $item['delta'] = $delta;
      $item['selected_collection'] = $collection;
    }

    return $values;
  }

  /**
   * Generates a custom name for an icon collection.
   *
   * @param array $collection
   *   An associative array containing details of the icon collection.
   *   Expected keys are 'name', 'category', and 'total'.
   * @param string $collection_id
   *   The unique identifier for the icon collection.
   *
   * @return string
   *   A formatted string containing the collection name, category, total
   *   number of icons, and a link to view the icons.
   */
  protected function getCustomCollectionName(array $collection, string $collection_id): string {
    return sprintf(
      '<strong>%s</strong> - %s (%d) <a href="https://icon-sets.iconify.design/%s" target="_blank">See icons</a>',
      $collection['name'] ?? 'Unknown Name',
      $collection['category'] ?? 'Uncategorized',
      $collection['total'] ?? 0,
      $collection_id
    );
  }

  /**
   * Gets a sorted list of icon collections with custom names.
   *
   * @return array
   *   An associative array where the keys are collection IDs and the values
   *   are custom collection names.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  protected function getOptions(): array {
    // Fetch and sort collections by 'total' in descending order.
    $collections = $this->iconify->getCollections();
    uasort($collections, fn($a, $b) => $b['total'] <=> $a['total']);

    $options = [];

    foreach ($collections as $collection_id => $collection) {
      $options[$collection_id] = $this->getCustomCollectionName($collection, $collection_id);
    }

    return $options;
  }

  /**
   * Extracts icon name and collection name from the given string.
   *
   * @param string $icon
   *   The icon string in the format 'Icon name (collection name)'.
   *
   * @return array|null
   *   An array containing the icon name and collection name, or null if not
   *   matched.
   */
  protected function extractIconDetails(string $icon): ?array {
    if (preg_match('/(.+)\s\(([^)]+)\)/', $icon, $matches)) {
      return [trim($matches[1]), trim($matches[2])];
    }

    return NULL;
  }

}
