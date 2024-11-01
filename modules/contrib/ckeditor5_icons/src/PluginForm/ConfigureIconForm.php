<?php

namespace Drupal\ckeditor5_icons\PluginForm;

use Drupal\ckeditor5_icons\CKEditor5IconsInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the CKEditor 5 Icon plugin.
 *
 * This form allows text format admins to configure:
 *  - the Font Awesome version (6 or 5).
 *  - the Font Awesome metadata.
 *  - available Font Awesome styles.
 *  - the "Recommended" category.
 */
class ConfigureIconForm extends PluginFormBase implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The example icons to show in "Recommended".
   *
   * @var string[]
   */
  protected const RECOMMENDED_ICONS = [
    'drupal',
    'plus',
    'font-awesome',
    'equals',
    'heart',
  ];

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\ckeditor5_icons\Plugin\CKEditor5Plugin\Icon
   */
  protected $plugin;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module's service.
   *
   * @var \Drupal\ckeditor5_icons\CKEditor5IconsInterface
   */
  protected $service;

  /**
   * Constructs a ConfigureIconForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\ckeditor5_icons\CKEditor5IconsInterface $service
   *   The module's service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, CKEditor5IconsInterface $service) {
    $this->moduleHandler = $moduleHandler;
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('ckeditor5_icons.CKEditor5Icons')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $libraryVersions = $this->service->getPreciseLibraryVersions();
    $faStyles = $this->service->getFontAwesomeStyles();
    $configuration = $this->plugin->getConfiguration();
    $editorConfig = $this->plugin->getPluginDefinition()->getCKEditor5Config()['icon'];
    $faModuleExists = $this->moduleHandler->moduleExists('fontawesome');

    $form['fa_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Awesome library version'),
      '#description' => $this->t('The selected version must match the version of the library already included on the site.'),
      '#default_value' => $configuration['fa_version'] ?? $editorConfig['faVersion'],
      '#options' => [
        '6' => $this->t('Font Awesome 6'),
        '5' => $this->t('Font Awesome 5'),
      ],
    ];
    $form['custom_metadata'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Awesome metadata'),
      '#description' => $this->t('The Font Awesome Free metadata uses version @fa_6_v or @fa_5_v.
       To supply Font Awesome Pro metadata or a custom version, select Custom.',
        [
          '@fa_6_v' => $libraryVersions['fontawesome6'],
          '@fa_5_v' => $libraryVersions['fontawesome5'],
          '@fa_module_link' => 'https://www.drupal.org/project/fontawesome',
        ]),
      '#default_value' => $faModuleExists ? $configuration['custom_metadata'] : 0,
      '#options' => [
        $this->t('Font Awesome Free'),
        $this->t('Custom'),
      ],
    ];
    $form['async_metadata'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load metadata asynchronously (recommended)'),
      '#description' => $this->t("Loads the Font Awesome metadata only when the icon picker is opened to decrease the page size and load time. Also allows the metadata to be cached by browsers. It's recommended to leave this enabled except for troubleshooting problems."),
      '#default_value' => $configuration['async_metadata'],
    ];
    $form['fa_styles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Font Awesome styles'),
    ];
    foreach ($faStyles as $styleName => $style) {
      $formElementId = 'fa_styles_' . $styleName;
      $form['fa_styles'][$formElementId] = [
        '#type' => 'checkbox',
        '#title' => $style['label'],
        '#default_value' => in_array($styleName, $configuration['fa_styles'] ?? $editorConfig['faStyles']),
      ];
      if ($style['pro']) {
        $form['fa_styles'][$formElementId]['#description'] = $this->t('Requires Font Awesome Pro.');
      }
    }
    $form['recommended_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the Recommended category'),
      '#default_value' => $configuration['recommended_enabled'] || $editorConfig['recommendedIcons'] !== NULL,
    ];
    $form['recommended_icons'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommended icons'),
      '#description' => $this->t('Comma-separated icon names to display in the Recommended category. For a complete list of icon names visit <a target="_blank" href="@fa_url">Font Awesome\'s website</a>.', ['@fa_url' => 'https://fontawesome.com/search?m=free']),
      '#default_value' => implode(',', $configuration['recommended_icons'] ?? ($editorConfig['recommendedIcons'] ?? static::RECOMMENDED_ICONS)),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $faVersion = $this->service->toValidFontAwesomeVersion($form_state->getValue('fa_version'));
    $faStyles = $this->service->getFontAwesomeStyles();

    $selectedStyles = [];
    foreach ($faStyles as $styleName => $style) {
      $formElementId = 'fa_styles_' . $styleName;
      if ($form_state->getValue('fa_styles')[$formElementId]) {
        $selectedStyles[] = $styleName;
        if (!in_array($faVersion, $style['compatibility'])) {
          $form_state->setError($form['fa_styles'][$formElementId], $this->t('The %s style is incompatible with the selected version of the Font Awesome library.', ['%s' => $style['label']]));
          return;
        }
      }
    }

    $form_state->setValue('fa_version', $faVersion);
    $form_state->setValue('fa_styles', $selectedStyles);
    $form_state->setValue('custom_metadata', (bool) $form_state->getValue('custom_metadata'));
    $form_state->setValue('async_metadata', (bool) $form_state->getValue('async_metadata'));
    $form_state->setValue('recommended_enabled', (bool) $form_state->getValue('recommended_enabled'));
    $form_state->setValue('recommended_icons', array_filter(array_map(function ($value) {
      return preg_replace('/([^a-z0-9\-]+)/', '', strtolower($value));
    }, explode(',', $form_state->getValue('recommended_icons')))));

    if (!$this->moduleHandler->moduleExists('fontawesome') && $form_state->getValue('custom_metadata')) {
      $form_state->setError($form['custom_metadata'], $this->t('<a target="_blank" href="@fa_module_link">Font Awesome Icons</a> must be installed to use custom Font Awesome metadata.', ['@fa_module_link' => 'https://www.drupal.org/project/fontawesome']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->plugin->getConfiguration();

    $configuration['fa_version'] = $form_state->getValue('fa_version');
    $configuration['fa_styles'] = $form_state->getValue('fa_styles');
    $configuration['custom_metadata'] = $form_state->getValue('custom_metadata');
    $configuration['async_metadata'] = $form_state->getValue('async_metadata');
    $configuration['recommended_enabled'] = $form_state->getValue('recommended_enabled');
    $recommendedIcons = $form_state->getValue('recommended_icons');
    if ($configuration['recommended_enabled'] || isset($configuration['recommended_icons']) || $recommendedIcons != static::RECOMMENDED_ICONS) {
      $configuration['recommended_icons'] = $form_state->getValue('recommended_icons');
    }

    $this->plugin->setConfiguration($configuration);
  }

}
