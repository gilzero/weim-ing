<?php

declare(strict_types=1);

namespace Drupal\aws\Entity\Form;

use Drupal\aws\Traits\AwsServiceTrait;
use Drupal\aws\Traits\ProfileEntityFormTrait;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm form for deleting a profile.
 */
class ProfileDeleteConfirmForm extends EntityDeleteForm {

  use AwsServiceTrait;
  use ProfileEntityFormTrait;

  /**
   * The services affected by deleting the profile.
   *
   * @var array
   */
  protected $affectedServices;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static())
      ->setAws($container->get('aws'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the profile %name?', [
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.aws_profile.edit_form', [
      'aws_profile' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $services = $this->aws->getServices();

    foreach ($services as $id => $service) {
      $settings = $this->aws->getServiceConfig($id);

      if ($this->entity->id() == $settings['profile']) {
        $this->affectedServices[$id] = $service['namespace'];
      }
    }

    if ($this->affectedServices) {
      $form['services'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('The following services currently use this profile.
          They will be reset to the default profile.'),
        'services' => [
          '#theme' => 'item_list',
          '#items' => $this->affectedServices,
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entity->isDefault()) {
      $form_state->setError($form, $this->t('The default profile cannot be deleted.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory()->getEditable('aws.settings');

    foreach ($this->affectedServices as $id => $service) {
      $settings = $config->get($id);

      if ($settings) {
        $settings['profile'] = 'default';
        $config->set($id, $settings);
        $config->save();
      }
    }
  }

}
