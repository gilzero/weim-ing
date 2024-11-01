<?php

namespace Drupal\push_framework\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Push Framework settings.
 */
abstract class Settings extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config entity.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pluginConfig;

  /**
   * Indicating if this is the default configuration form.
   *
   * @var bool
   */
  private bool $isDefault;

  /**
   * The entity view mode storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityViewModeStorage;

  /**
   * {@inheritdoc}
   */
  final public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;

    $configName = $this->getEditableConfigNames()[0];
    $this->pluginConfig = $this->config($configName);
    $this->isDefault = $configName === 'push_framework.settings';
    try {
      $this->entityViewModeStorage = $this->entityTypeManager->getStorage('entity_view_mode');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // This shouldn't ever happen, unless something bigger is broken.
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'push_framework_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['push_framework.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content formatting and rendering'),
      '#open' => TRUE,
      '#weight' => 21,
    ];
    if (!$this->isDefault) {
      $form['active'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Active'),
        '#default_value' => $this->pluginConfig->get('active'),
        '#weight' => -1,
      ];
      $form['use_default_settings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use default settings for content'),
        '#default_value' => $this->pluginConfig->get('use_default_settings'),
        '#weight' => 20,
        '#states' => [
          'visible' => [
            ':input[name="active"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['content']['#states'] = [
        'visible' => [
          ':input[name="active"]' => ['checked' => TRUE],
          ':input[name="use_default_settings"]' => ['checked' => FALSE],
        ],
      ];
    }
    $form['content']['display_modes'] = [
      '#type' => 'details',
      '#title' => $this->t('Display modes'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $modes = [];
        if (!$this->isDefault) {
          $modes[''] = $this->t('default');
        }
        foreach ($this->entityViewModeStorage->loadByProperties(['targetEntityType' => $definition->id()]) as $mode) {
          $parts = explode('.', $mode->id());
          $modes[array_pop($parts)] = $mode->label();
        }
        if (empty($modes)) {
          $modes['teaser'] = $this->t('Teaser');
        }
        if (!$this->isDefault && $this->pluginConfig->get('use_default_settings')) {
          $default = '';
        }
        else {
          $default = $this->pluginConfig->get('display_modes.' . $definition->id());
          if (empty($default) && isset($modes['teaser'])) {
            $default = $this->isDefault ? 'teaser' : '';
          }
        }
        $form['content']['display_modes'][$definition->id()] = [
          '#type' => 'select',
          '#title' => $definition->getLabel(),
          '#options' => $modes,
          '#default_value' => $default,
        ];
      }
    }

    $form['content']['pattern'] = [
      '#type' => 'details',
      '#title' => $this->t('Content pattern'),
      '#open' => TRUE,
    ];
    if (!$this->isDefault) {
      $form['content']['pattern']['#description'] = $this->t('Leave those pattern fields empty that should use default settings.');
    }
    $form['content']['pattern']['pattern_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => !$this->isDefault && $this->pluginConfig->get('use_default_settings') ? '' : $this->pluginConfig->get('pattern.subject'),
    ];
    $form['content']['pattern']['pattern_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => !$this->isDefault && $this->pluginConfig->get('use_default_settings') ? '' : $this->pluginConfig->get('pattern.body.value'),
      '#format' => !$this->isDefault && $this->pluginConfig->get('use_default_settings') ? 'plain_text' : $this->pluginConfig->get('pattern.body.format'),
      '#allowed_formats' => array_keys(filter_formats($this->currentUser())),
    ];
    $form['content']['pattern']['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'entity' => 'user',
        'push-object' => 'push-object',
      ],
      '#global_types' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->isDefault) {
      $this->pluginConfig
        ->set('active', $form_state->getValue('active'))
        ->set('use_default_settings', $form_state->getValue('use_default_settings'));
    }
    $this->pluginConfig
      ->set('pattern.subject', $form_state->getValue('pattern_subject'))
      ->set('pattern.body', $form_state->getValue('pattern_body'));
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $this->pluginConfig->set('display_modes.' . $definition->id(), $form_state->getValue([
          'display_modes',
          $definition->id(),
        ]));
      }
    }
    $this->pluginConfig->save();
    parent::submitForm($form, $form_state);
  }

}
