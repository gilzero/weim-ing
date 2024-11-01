<?php

declare(strict_types=1);

namespace Drupal\aws\Form;

use Drupal\aws\Traits\AwsServiceTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm deleting an AWS service override.
 */
class ServiceOverrideDeleteForm extends ConfirmFormBase {

  use AwsServiceTrait;

  /**
   * The ID service being deleted.
   *
   * @var string
   */
  protected $serviceId;

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
  public function getFormId() {
    return 'aws_service_override_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $service = $this->aws->getService($this->serviceId);

    return $this->t('Are you sure you want to delete the override for %service?', [
      '%service' => $service['namespace'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('aws.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $service_id = '') {
    $this->serviceId = $service_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $service = $this->aws->getService($this->serviceId);
    $this->aws->setServiceConfig($this->serviceId, NULL);

    $this->messenger()->addStatus($this->t('The override for %service has been
    deleted.', [
      '%service' => $service['namespace'],
    ]));

    $form_state->setRedirect('aws.overview');
  }

}
