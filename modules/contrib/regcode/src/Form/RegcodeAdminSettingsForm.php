<?php

declare(strict_types=1);

namespace Drupal\regcode\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration settings for the registration codes module.
 */
class RegcodeAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['regcode.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'regcode_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $regcode_config = $this->config('regcode.settings');

    $form = [];
    $form['regcode_forms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Registration form field'),
    ];
    $form['regcode_forms']['regcode_field_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field label'),
      '#description' => $this->t('The label of the registration code textfield'),
      '#required' => TRUE,
      '#default_value' => $regcode_config->get('regcode_field_title'),
    ];
    $form['regcode_forms']['regcode_field_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field description'),
      '#description' => $this->t('The description under the registration code textfield'),
      '#rows' => 2,
      '#default_value' => $regcode_config->get('regcode_field_description'),
    ];
    $form['regcode_forms']['regcode_optional'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make registration code optional'),
      '#default_value' => $regcode_config->get('regcode_optional'),
      '#description' => $this->t('If checked, users can register without a registration code.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('regcode.settings')
      ->set('regcode_field_title', $form_state->getValue('regcode_field_title'))
      ->set('regcode_field_description', $form_state->getValue('regcode_field_description'))
      ->set('regcode_optional', $form_state->getValue('regcode_optional'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
