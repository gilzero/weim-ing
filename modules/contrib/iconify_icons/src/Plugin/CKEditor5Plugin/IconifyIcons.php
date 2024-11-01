<?php

declare(strict_types=1);

namespace Drupal\iconify_icons\Plugin\CKEditor5Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\editor\EditorInterface;
use Drupal\iconify_icons\IconifyServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 IconifyIcons plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class IconifyIcons extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * Constructs an IconifyIcons object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\iconify_icons\IconifyServiceInterface $iconifyService
   *   The Iconify service.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, protected IconifyServiceInterface $iconifyService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('iconify_icons.iconify_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'collections' => [],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Checkboxes for selecting the collection(s) to be available.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['search'] = [
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

    $form['collections'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions(),
      '#default_value' => array_keys($this->configuration['collections'] ?? []),
      '#description' => $this->t('Select the collections which are going to provide the set of icons. See @collectionIconsLink list. Leave empty to select all.', [
        '@collectionIconsLink' => Link::fromTextAndUrl($this->t('the Iconify icon collections'), Url::fromUri('https://icon-sets.iconify.design/', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#attributes' => [
        'class' => ['iconify-icons-widget-collections'],
      ],
      '#attached' => [
        'library' => ['iconify_icons/default'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $collections = array_filter($form_state->getValue('collections'));
    $this->configuration['collections'] = array_combine($collections, $collections);
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $static_plugin_config['iconifyIcons']['collections'] = implode(',', $this->configuration['collections'] ?? []);
    return $static_plugin_config;
  }

  /**
   * Gets a formatted name for an icon collection.
   *
   * This method constructs a custom name for an icon collection using its name,
   * category, total number of icons, and a hyperlink to view the icons.
   *
   * @param array $collection
   *   An associative array containing the details of the collection.
   * @param string $collection_id
   *   The unique identifier for the collection.
   *
   * @return string
   *   The formatted custom name for the collection.
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
   * This function fetches icon collections from the Iconify service,
   * sorts them by the total number of icons in descending order,
   * and then maps them to custom names for each collection.
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
    $collections = $this->iconifyService->getCollections();
    uasort($collections, fn($a, $b) => $b['total'] <=> $a['total']);

    $options = [];

    foreach ($collections as $collection_id => $collection) {
      $options[$collection_id] = $this->getCustomCollectionName($collection, $collection_id);
    }

    return $options;
  }

}
