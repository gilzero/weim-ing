<?php

namespace Drupal\phpmailer_smtp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure PHPMailer SMTP settings.
 */
class FormatForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpmailer_smtp_format';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phpmailer_smtp.format'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get mutable config to exclude overrides.
    $config = $this->configFactory()->getEditable('phpmailer_smtp.format');

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Email format'),
      '#default_value' => $config->get('format'),
      '#options' => [
        'plain_text' => $this->t('Plain text'),
        'html' => $this->t('HTML'),
      ],
      '#description' => $this->t('Setting the format to HTML will cause the "Content-Type" header to be respected.'),
    ];

    $form['force_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send all email as HTML'),
      '#default_value' => $config->get('force_html'),
      '#description' => $this->t('Send all email as HTML ignoring the "Content-Type" header.'),
      '#states' => [
        'visible' => [
          ':input[name="format"]' => ['value' => 'html'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the configuration changes.
    $phpmailer_smtp_config = $this->config('phpmailer_smtp.format');
    $phpmailer_smtp_config->set('format', $values['format'])
      ->set('force_html', $values['force_html'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
