<?php

namespace Drupal\rest_log\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Rest log settings for this site.
 */
class RestLogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rest_log_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rest_log.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['maximum_lifetime'] = [
      '#type' => 'number',
      '#min' => 0,
      '#default_value' => $this->config('rest_log.settings')->get('maximum_lifetime'),
      '#title' => $this->t('Maximum lifetime'),
      '#description' => $this->t('Delete logs older than maximum lifetime, automatic cleanup disabled if it set to 0.'),
    ];
    $form['include_same_host'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->config('rest_log.settings')->get('include_same_host'),
      '#title' => $this->t('Include same host'),
      '#description' => $this->t('Include or exclude logs with same-host referrer HTTP header.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rest_log.settings')
      ->set('maximum_lifetime', $form_state->getValue('maximum_lifetime'))
      ->set('include_same_host', $form_state->getValue('include_same_host'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
