<?php

namespace Drupal\moderation_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The settings form for Moderation Dashboard.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['moderation_dashboard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'moderation_dashboard_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('moderation_dashboard.settings');

    $form['redirect_on_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect on login'),
      '#default_value' => $config->get('redirect_on_login'),
      '#description' => $this->t('Redirect to moderation dashboard after login.'),
    ];

    $form['chart_js_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pull chart.js from CDN'),
      '#default_value' => $config->get('chart_js_cdn'),
      '#description' => $this->t("Pull in chart.js from the CDN or leave uncheck to use local copy. For security it's best to use local copy"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config('moderation_dashboard.settings')
      ->set('redirect_on_login', $values['redirect_on_login'])
      ->set('chart_js_cdn', $values['chart_js_cdn'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
