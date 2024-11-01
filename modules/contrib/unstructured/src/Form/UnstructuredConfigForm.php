<?php

namespace Drupal\unstructured\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Unstructured API access.
 */
class UnstructuredConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'unstructured.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unstructured_settings';
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

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Unstructured API Key'),
      '#description' => $this->t('The API Key if one is needed. If you are using api.unstructured.io, you need one.'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unstructured API Host'),
      '#description' => $this->t('The host to use for the API. If you are using api.unstructured.io, you can leave this blank. If you are using an organization api from unstructured, you need the Base URL from the email unstructured sent.'),
      '#default_value' => $config->get('host') ?? 'https://api.unstructured.io',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('host', $form_state->getValue('host'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
