<?php

declare(strict_types=1);

namespace Drupal\bpmn_io\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a BPMN.iO for ECA form.
 */
final class Modeller extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bpmn_io_modeller';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = [
      '#type' => 'actions',
      'save' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#attributes' => [
          'class' => ['button--primary eca-save'],
        ],
      ],
      'close' => [
        '#type' => 'submit',
        '#value' => $this->t('Close'),
        '#attributes' => [
          'class' => ['eca-close'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
