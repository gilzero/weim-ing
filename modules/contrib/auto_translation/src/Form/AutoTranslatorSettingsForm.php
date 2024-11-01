<?php

namespace Drupal\auto_translation\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with auto_translation on how to use cron.
 */
class AutoTranslatorSettingsForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, AccountInterface $current_user, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, ModuleHandler $moduleHandler) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('current_user'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_translation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('auto_translation.settings');
    $node_options = [];
    $media_options = [];
    $media_options_default = [];
    $node_options_default = [];
    $nodes_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    if ($this->moduleHandler->moduleExists('media')) {
      $media_types = $this->entityTypeManager
        ->getStorage('media_type')
        ->loadMultiple();
    }
    foreach ($nodes_types as $type) {
      $node_options[$type->id()] = $type->label();
      $node_options_default[$type->id()] = $type->id();
    }
    if ($this->moduleHandler->moduleExists('media') && $media_types) {
      foreach ($media_types as $type) {
        if ($type->id()) {
          $media_options[$type->id()] = $type->label();
          $media_options_default[$type->id()] = $type->id();
        }
      }
      $node_options_default = array_merge($media_options_default, $node_options_default);
      $node_options = array_merge($media_options, $node_options);
    }
    if (!empty($config->get('auto_translation_content_types'))) {
      $enabled_content_type = $config->get('auto_translation_content_types');
    }
    else {
      $enabled_content_type = $node_options_default;
    }

    $form['configuration_nodes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Auto Translation - configuration'),
      '#open' => TRUE,
    ];

    $form['configuration_nodes']['providers_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto Translation - Providers configuration'),
      '#open' => TRUE,
    ];

    $form['configuration_nodes']['content_types_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto Translation - Content types configuration'),
      '#open' => TRUE,
    ];

    $form['configuration_nodes']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('You can select content types where enable the auto translation, by default is enabled on all content types with Google Translate browser Free API.'),
      '#weight' => -10,
    ];
    $form['configuration_nodes']['providers_list']['auto_translation_provider'] = [
      '#title' => $this->t('Translator Provider'),
      '#type' => 'select',
      '#description'   => $this->t('Select auto translator Provider'),
      '#options' => [
        'google' => $this->t('Google Translate API'),
        'libretranslate' => $this->t('Libre Translate API'),
        'drupal_ai' => $this->t('Drupal AI'),
      ],
      '#default_value' => $config->get('auto_translation_provider') ? $config->get('auto_translation_provider') : 'google',
      '#required'      => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="server_side_poc"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['configuration_nodes']['content_types_list']['auto_translation_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Content Types'),
      '#description' => $this->t('Define what content types will be enabled for content auto translation.'),
      '#default_value' => $enabled_content_type,
      '#options' => $node_options,
    ];

    $form['configuration_nodes']['content_types_list']['auto_translation_excluded_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded fields'),
      '#description' => $this->t('Define what fields will be excluded from content auto translation, separated by comma.'),
      '#default_value' => $config->get('auto_translation_excluded_fields'),
      '#placeholder' => $this->t('field_name_1, field_name_2, field_name_3'),
    ];
    $form['configuration_nodes']['api_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto Translation - API configuration'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [
            ':input[name="auto_translation_provider"]' => ['value' => 'google'],
          ],
          'and',
          [
            ':input[name="auto_translation_provider"]' => ['value' => 'libretranslate'],
          ],
        ],
      ],
    ];
    $form['configuration_nodes']['api_settings']['auto_translation_api_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Server Side API'),
      '#description' => $this->t('Enable server side API for content auto translation, if unchecked Google Translate browser Free API will be used.'),
      '#default_value' => $config->get('auto_translation_api_enabled'),
      '#states' => [
        'visible' => [
          ':input[name="auto_translation_provider"]' => ['value' => 'google'],
        ],
      ],
    ];

    $form['configuration_nodes']['api_settings']['auto_translation_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Enter your API key.'),
      '#default_value' => $config->get('auto_translation_api_key'),
      '#states' => [
        'visible' => [
          [':input[name="auto_translation_api_enabled"]' => ['checked' => TRUE]],
          'and',
          [
            ':input[name="auto_translation_provider"]' => ['value' => 'libretranslate'],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the values as stored in configuration. This will be read when.
    $this->configFactory->getEditable('auto_translation.settings')
      ->set('interval', $form_state->getValue('auto_translation_interval'))
      ->set('auto_translation_content_types', $form_state->getValue('auto_translation_content_types'))
      ->set('auto_translation_api_enabled', $form_state->getValue('auto_translation_api_enabled'))
      ->set('auto_translation_api_key', $form_state->getValue('auto_translation_api_key'))
      ->set('auto_translation_excluded_fields', $form_state->getValue('auto_translation_excluded_fields'))
      ->set('auto_translation_provider', $form_state->getValue('auto_translation_provider'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['auto_translation.settings'];
  }

}
