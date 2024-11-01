<?php

namespace Drupal\google_places\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GooglePlaces API access.
 */
class GooglePlacesConfigForm extends ConfigFormBase {


  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_automator_address.google_places_settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_automator_address_google_places_settings';
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
      '#title' => $this->t('Google Places API Key'),
      '#description' => $this->t('Can be found and generated <a href="https://beta.google_places.ai/account" target="_blank">here</a>.'),
      '#default_value' => $config->get('api_key'),
      '#states' => [
        'visible' => [
          ':input[name="setting_type"]' => ['value' => 'api_key'],
        ],
      ],
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
      ->save();

    parent::submitForm($form, $form_state);
  }

}
