<?php

namespace Drupal\ai_provider_aws_bedrock\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AWS Bedrock API access.
 */
class BedrockConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_provider_aws_bedrock.settings';

  /**
   * Constructs a new AWS Bedrock Config object.
   */
  final public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bedrock_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $profiles = $this->getAllProfiles();
    if (empty($profiles)) {
      $form['no_profiles'] = [
        '#markup' => '<p>' . $this->t('No profiles found. Please create a profile first in the %link settings page.', [
          '%link' => Link::createFromRoute($this->t('Amazon Web Services'), 'aws.overview')->toString(),
        ]) . '</p>',
      ];
      return $form;
    }

    $form['profile'] = [
      '#type' => 'select',
      '#title' => $this->t('AWS Bedrock Profile'),
      '#options' => $profiles,
      '#required' => TRUE,
      '#empty_option' => $this->t('-- Select a profile --'),
      '#description' => $this->t('The AWS Profile to use by default. Can be setup in the %link settings page.', [
        '%link' => Link::createFromRoute($this->t('Amazon Web Services'), 'aws.overview')->toString(),
      ]
      ),
      '#default_value' => $config->get('profile'),
    ];

    $form['on_demand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show On-Demand Models'),
      '#description' => $this->t('If checked, only models that are on-demand will be shown. If you need provisioned models, uncheck this.'),
      '#default_value' => $config->get('on_demand'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('profile', $form_state->getValue('profile'))
      ->set('on_demand', $form_state->getValue('on_demand'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get all profiles.
   *
   * @return array
   *   An array of profiles.
   */
  protected function getAllProfiles() {
    $profiles = [];
    foreach ($this->entityTypeManager->getStorage('aws_profile')->loadMultiple() as $profile) {
      $profiles[$profile->id()] = $profile->label();
    }
    return $profiles;
  }

}
