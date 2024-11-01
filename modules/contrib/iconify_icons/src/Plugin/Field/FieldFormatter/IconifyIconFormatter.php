<?php

namespace Drupal\iconify_icons\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\iconify_icons\IconifyServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of Iconify icon formatter.
 *
 * @FieldFormatter(
 *   id = "iconify_icon_formatter",
 *   label = @Translation("Iconify Icon"),
 *   field_types = {
 *     "iconify_icon"
 *   }
 * )
 */
class IconifyIconFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactory $config_factory, IconifyServiceInterface $iconify) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
    $this->iconify = $iconify;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('iconify_icons.iconify_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'advanced_settings' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $advanced_settings = $this->getSetting('advanced_settings');

    $elements['advanced_settings'] = [
      '#type' => 'container',
    ];

    $elements['advanced_settings']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Icon dimensions in pixels. If only one dimension is specified, such as height, other dimension will be automatically set to match it.'),
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#field_suffix' => $this->t('pixels'),
      '#default_value' => $advanced_settings['width'] ?? 50,
    ];

    $elements['advanced_settings']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Icon dimensions in pixels. If only one dimension is specified, such as height, other dimension will be automatically set to match it.'),
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#field_suffix' => $this->t('pixels'),
      '#default_value' => $advanced_settings['height'] ?? 50,
    ];

    $elements['advanced_settings']['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#description' => $this->t('Icon color. Sets color for monotone icons.'),
      '#default_value' => $advanced_settings['color'] ?? '',
    ];

    $elements['advanced_settings']['flip'] = [
      '#type' => 'select',
      '#title' => $this->t('Flip'),
      '#empty_option' => $this->t('None'),
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#description' => $this->t('Flip icon.'),
      '#default_value' => $advanced_settings['flip'] ?? '',
    ];

    $elements['advanced_settings']['rotate'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#empty_option' => $this->t('None'),
      '#options' => [
        '90' => $this->t('90°'),
        '180' => $this->t('180°'),
        '270' => $this->t('270°'),
      ],
      '#description' => $this->t('Rotate icon by 90, 180 or 270 degrees.'),
      '#default_value' => $advanced_settings['rotate'] ?? '',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $advanced_settings = $this->getSetting('advanced_settings');

    // Define the settings with their default values.
    $settings = [
      'width' => 25,
      'height' => 25,
      'color' => 'currentColor',
      'flip' => '',
      'rotate' => '',
    ];

    // Create the summary output.
    $output = [];
    foreach ($settings as $key => $default) {
      if (isset($advanced_settings[$key]) && $default) {
        $output[] = ucfirst($key) . ': ' . $advanced_settings[$key];
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return [];
    }

    $iconify_icons = $items->getValue();

    // Loop over each icon and build data.
    $icons = [];
    foreach ($iconify_icons as $icon) {
      // Link settings coming from the Iconify icons link widget (URL and Link
      // text).
      $link_settings = unserialize($icon['settings'] ?? '', ['allowed_classes']) ?: [];
      // Advanced settings coming from field formatter settings.
      $advanced_settings = $this->getSetting('advanced_settings') ?? [];

      $icons[] = [
        '#type' => 'iconify_icon',
        '#icon' => $icon['icon'],
        '#settings' => array_merge($link_settings, $advanced_settings),
      ];
    }

    return [
      [
        '#theme' => 'iconify_icons',
        '#icons' => $icons,
        '#attached' => [
          'library' => [
            'iconify_icons/default',
          ],
        ],
      ],
    ];
  }

}
