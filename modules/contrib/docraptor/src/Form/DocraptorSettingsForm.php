<?php

declare(strict_types=1);

namespace Drupal\docraptor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Docraptor settings for this site.
 */
final class DocraptorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'docraptor_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['docraptor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $key_id = 'docraptor';
    $config = $this->config('docraptor.settings');

    $form['enable_test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Test'),
      '#description' => $this->t('TBA.'),
      '#default_value' => $config->get('enable_test') ?? FALSE,
    ];

    $form['username_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $config->get('username_key') ?? '',
    ];

    $form['document_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document type'),
      '#default_value' => $config->get('document_type') ?? 'pdf',
    ];

    $form['pdf_profile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PDF profile'),
      '#default_value' => $config->get('pdf_profile') ?? 'PDF/UA-1',
    ];

    $form['color_conversion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color conversion'),
      '#default_value' => $config->get('color_conversion') ?? 'sRGB',
    ];

    $form['enable_pdf_forms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PDF forms'),
      '#description' => $this->t('TBA.'),
      '#default_value' => $config->get('enable_pdf_forms') ?? TRUE,
    ];

    $form['enable_icc_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ICC profile'),
      '#description' => $this->t('TBA.'),
      '#default_value' => $config->get('enable_icc_profile') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('docraptor.settings')
      ->set('enable_test', $form_state->getValue('enable_test'))
      ->set('username_key', $form_state->getValue('username_key'))
      ->set('document_type', $form_state->getValue('document_type'))
      ->set('pdf_profile', $form_state->getValue('pdf_profile'))
      ->set('color_conversion', $form_state->getValue('color_conversion'))
      ->set('enable_pdf_forms', $form_state->getValue('enable_pdf_forms'))
      ->set('enable_icc_profile', $form_state->getValue('enable_icc_profile'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
