<?php

declare(strict_types=1);

namespace Drupal\aws\Entity\Form;

use Drupal\aws\Traits\AwsServiceTrait;
use Drupal\aws\Traits\ProfileEntityFormTrait;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing AWS profiles.
 */
class ProfileForm extends EntityForm {

  use AwsServiceTrait;
  use ProfileEntityFormTrait;

  /**
   * The encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface|null
   */
  protected $encryptionProfileManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static())
      ->setAws($container->get('aws'))
      ->setEncryptionProfileManager($container->get(
        'encrypt.encryption_profile.manager',
        ContainerInterface::NULL_ON_INVALID_REFERENCE
      ));
  }

  /**
   * Set the encryption profile manager.
   *
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface|null $encryption_profile_manager
   *   The encryption profile manager.
   *
   * @return $this
   */
  protected function setEncryptionProfileManager(?EncryptionProfileManagerInterface $encryption_profile_manager) {
    $this->encryptionProfileManager = $encryption_profile_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    /** @var \Drupal\aws\Entity\ProfileInterface $entity */

    $profiles = $this->aws->getProfiles();
    if (count($profiles) == 0) {
      $entity->setDefault(TRUE);
    }

    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Set the page title according to whether we are creating or editing
    // the profile.
    if ($this->entity->isNew()) {
      $form['#title'] = $this->t('Add AWS Profile');
    }
    else {
      $form['#title'] = $this->t('Edit %label', [
        '%label' => $this->entity->label(),
      ]);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile name'),
      '#description' => $this->t('Enter the name for the profile.'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->isNew() ? NULL : $this->entity->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\aws\Entity\Profile::load',
        'source' => ['name'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#description' => $this->t('If selected, this profile will be used as the default.'),
      '#default_value' => (int) $this->entity->isDefault(),
      '#after_build' => ['::processDefault'],
    ];

    $form['aws_role_arn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role ARN'),
      '#description' => $this->t('AWS role arn.'),
      '#default_value' => $this->entity->getRoleArn(),
    ];

    $form['aws_role_session_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role Session Name'),
      '#description' => $this->t('The role session name for temporary credentials.'),
      '#default_value' => $this->entity->getRoleSessionName(),
    ];

    $form['aws_access_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#description' => $this->t('AWS access key.'),
      '#default_value' => $this->entity->getAccessKey(),
    ];

    $form['aws_secret_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Access Key'),
      '#description' => $this->t('AWS secret key.'),
      '#default_value' => $this->entity->getSecretAccessKey(),
    ];

    $form['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#description' => $this->t('AWS region.'),
      '#default_value' => $this->entity->getRegion(),
    ];

    if ($this->encryptionProfileManager) {
      $options = ['_none' => $this->t('- None -')];

      $encryption_profiles = $this->encryptionProfileManager->getAllEncryptionProfiles();
      foreach ($encryption_profiles as $id => $profile) {
        $options[$id] = $profile->label();
      }

      $form['encryption_profile'] = [
        '#type' => 'select',
        '#title' => $this->t('Encryption Profile'),
        '#description' => $this->t('The encryption profile to use to encrypt the secret key.'),
        '#options' => $options,
        '#default_value' => $this->entity->getEncryptionProfile(),
      ];
    }

    return $form;
  }

  /**
   * Process the default form element.
   *
   * To ensure that there is always a single default profile.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The processed element.
   */
  public function processDefault(array $element, FormStateInterface $form_state) {
    if ($element['#default_value']) {
      $element['#attributes']['disabled'] = 'disabled';
      $element['#description'] = $this->t('This profile will be used as the default.');
      $form_state->setValue('default', 1);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // The default profile should not be able to be deleted.
    if (isset($actions['delete']) && $this->entity->isDefault()) {
      unset($actions['delete']);
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\aws\Entity\ProfileInterface $profile */
    $profile = parent::buildEntity($form, $form_state);

    if (!$form_state->isValueEmpty('aws_secret_access_key')) {
      $profile->setSecretAccessKey($form_state->getValue('aws_secret_access_key'));
    }

    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('default')) {
      $default_profile = $this->aws->getDefaultProfile();

      if ($default_profile && $default_profile->id() != $form_state->getValue('id')) {
        $default_profile
          ->setDefault(FALSE)
          ->save();
      }
    }

    $status = parent::save($form, $form_state);

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %name profile has been created.', [
        '%name' => $form_state->getValue('name'),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %name profile has been saved.', [
        '%name' => $form_state->getValue('name'),
      ]));
    }

    $form_state->setRedirect('aws.overview');
  }

}
