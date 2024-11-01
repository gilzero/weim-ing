<?php

declare(strict_types=1);

namespace Drupal\aws\Form;

use Drupal\aws\Traits\AwsServiceTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure an AWS service override.
 */
class ServiceOverrideForm extends ConfigFormBase {

  use AwsServiceTrait;

  /**
   * Config settings name.
   *
   * @var string
   */
  const CONFIG_NAME = 'aws.settings';

  /**
   * The ID of the service being configured.
   *
   * @var string
   */
  protected $serviceId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_service_override_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var self $instance */
    $instance = parent::create($container);
    $instance->setAws($container->get('aws'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $service_id = '') {
    $form = parent::buildForm($form, $form_state);

    if (!$service_id) {
      $service_options = [];
      $services = $this->aws->getServices();
      foreach ($services as $id => $service) {
        $service_options[$id] = $service['namespace'];
      }

      $form['service'] = [
        '#type' => 'select',
        '#title' => $this->t('Service'),
        '#description' => $this->t('The service to configure.'),
        '#options' => $service_options,
        '#ajax' => [
          'callback' => '::loadVersions',
          'event' => 'change',
          'wrapper' => 'service-versions',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Loading versions...'),
          ],
        ],
      ];
    }

    if ($form_state->getValue('service')) {
      $service_id = $form_state->getValue('service');
    }

    $settings = $this->aws->getServiceConfig($service_id);
    $this->serviceId = $service_id;

    $profile_options = [];
    /** @var \Drupal\aws\Entity\Profile $profile */
    foreach ($this->aws->getProfiles() as $profile) {
      $profile_options[$profile->id()] = $profile->label();
    }

    $form['profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Profile'),
      '#description' => $this->t('The profile that will be used to authenticate this service.'),
      '#options' => $profile_options,
      '#default_value' => $settings['profile'] ?? NULL,
    ];

    $version_options = [];

    if ($service_id) {
      $version_options = $this->getVersions($service_id);
    }

    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version'),
      '#description' => $this->t('The version of the service that this override affects.'),
      '#options' => $version_options,
      '#default_value' => $settings['version'] ?? NULL,
      '#prefix' => '<div id="service-versions">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->aws->setServiceConfig($this->serviceId, [
      'profile' => $form_state->getValue('profile'),
      'version' => $form_state->getValue('version'),
    ]);

    $this->messenger()->addStatus($this->t('The override has been saved'));
    $form_state->setRedirect('aws.overview');
  }

  /**
   * Update the form for the selected service.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array of updated form elements.
   */
  public function loadVersions(array &$form, FormStateInterface $form_state) {
    $service_id = $form_state->getValue('service');
    $form['version']['#options'] = $this->getVersions($service_id);

    return $form['version'];
  }

  /**
   * Get the available versions for a given service.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return array
   *   An array of version options.
   */
  protected function getVersions($service_id) {
    $version_options = [];

    $service = $this->aws->getService($service_id);
    $versions = array_keys($service['versions']);

    foreach ($versions as $version) {
      $version_options[$version] = ucfirst($version);
    }

    return $version_options;
  }

}
