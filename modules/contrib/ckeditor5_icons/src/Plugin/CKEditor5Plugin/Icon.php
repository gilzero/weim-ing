<?php

namespace Drupal\ckeditor5_icons\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5_icons\CKEditor5IconsInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Icon plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Icon extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, PluginWithFormsInterface, ContainerFactoryPluginInterface {
  use CKEditor5PluginConfigurableTrait;

  /**
   * The plugin form.
   *
   * @var \Drupal\Core\Plugin\PluginFormInterface
   */
  protected $form;

  /**
   * The token generator for generating CSRF tokens.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * The module's service.
   *
   * @var \Drupal\ckeditor5_icons\CKEditor5IconsInterface
   */
  protected $service;

  /**
   * The Font Awesome manager from the contrib module (optional).
   *
   * @var \Drupal\fontawesome\FontAwesomeManager|null
   */
  protected $fontAwesomeManager;

  /**
   * Constructs an Icon object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $tokenGenerator
   *   The token generator for generating CSRF tokens.
   * @param \Drupal\ckeditor5_icons\CKEditor5IconsInterface $service
   *   The module's service.
   * @param \Drupal\fontawesome\FontAwesomeManager|null $fontAwesomeManager
   *   The Font Awesome manager from the contrib module (optional).
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginFormFactoryInterface $pluginFormFactory, CsrfTokenGenerator $tokenGenerator, CKEditor5IconsInterface $service, $fontAwesomeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->form = $pluginFormFactory->createInstance($this, 'configure');
    $this->tokenGenerator = $tokenGenerator;
    $this->service = $service;
    $this->fontAwesomeManager = $fontAwesomeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin_form.factory'),
      $container->get('csrf_token'),
      $container->get('ckeditor5_icons.CKEditor5Icons'),
      $container->get('module_handler')->moduleExists('fontawesome') ? $container->get('fontawesome.font_awesome_manager') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'custom_metadata' => FALSE,
      'async_metadata' => TRUE,
      'recommended_enabled' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->form->buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->form->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->form->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $staticConfig = $static_plugin_config['icon'];
    $dynamicConfig = $staticConfig;

    if (isset($this->configuration['fa_version'])) {
      $dynamicConfig['faVersion'] = $this->configuration['fa_version'];
    }
    $faVersion = $dynamicConfig['faVersion'];

    $customMetadata = $this->configuration['custom_metadata'] && $this->fontAwesomeManager !== NULL;
    $dynamicConfig['customMetadata'] = $customMetadata;

    // Provides the CSRF-protected ajax URI for asynchronous metadata loading.
    if ($this->configuration['async_metadata']) {
      $faMetadataVersion = $customMetadata ? '' : $faVersion;
      $metadataPath = $this->service->getFontAwesomeMetadataPath($faMetadataVersion);
      $query = [];
      if (!$customMetadata) {
        $query['version'] = $this->service->getPreciseLibraryVersions()['fontawesome' . $faVersion];
      }
      // Generates the CSRF token as a query parameter.
      $query['token'] = $this->tokenGenerator->get($metadataPath);
      // Sets `asyncMetadataURI` to the full URI with CSRF token.
      $dynamicConfig['asyncMetadataURI'] = Url::fromUri('internal:/' . $metadataPath, ['query' => $query])->toString();
    }
    // Provides the synchronous metadata.
    else {
      if ($customMetadata) {
        $dynamicConfig['faCategories'] = $this->fontAwesomeManager->getCategories();
        $dynamicConfig['faIcons'] = $this->fontAwesomeManager->getIcons();
      }
      else {
        $dynamicConfig['faCategories'] = $this->service->getFontAwesomeCategories($faVersion);
        $dynamicConfig['faIcons'] = $this->service->getFontAwesomeIcons($faVersion);
      }
    }

    if (isset($this->configuration['fa_styles'])) {
      $dynamicConfig['faStyles'] = $this->configuration['fa_styles'];
    }
    if ($this->configuration['recommended_enabled'] && isset($this->configuration['recommended_icons'])) {
      $dynamicConfig['recommendedIcons'] = $this->configuration['recommended_icons'];
    }

    return ['icon' => $dynamicConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClass($operation) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    return 'Drupal\ckeditor5_icons\PluginForm\ConfigureIconForm';
  }

}
