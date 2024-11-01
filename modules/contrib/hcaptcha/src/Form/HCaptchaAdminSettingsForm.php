<?php

namespace Drupal\hcaptcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure hCaptcha settings for this site.
 */
class HCaptchaAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hcaptcha_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hcaptcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hcaptcha.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => true,
    ];

    $form['general']['hcaptcha_site_key'] = [
      '#default_value' => $config->get('site_key'),
      '#description' => $this->t('The site key given to you when you <a href=":url">register for hCaptcha</a>.', [':url' => 'https://hcaptcha.com/?r=8a46bae6b225']),
      '#maxlength' => 50,
      '#required' => true,
      '#title' => $this->t('Site key'),
      '#type' => 'textfield',
    ];

    $form['general']['hcaptcha_secret_key'] = [
      '#default_value' => $config->get('secret_key'),
      '#description' => $this->t('The secret key given to you when you <a href=":url">register for hCaptcha</a>.', [':url' => 'https://hcaptcha.com/?r=8a46bae6b225']),
      '#maxlength' => 50,
      '#required' => true,
      '#title' => $this->t('Secret key'),
      '#type' => 'textfield',
    ];

    $form['general']['hcaptcha_src'] = [
      '#default_value' => $config->get('hcaptcha_src'),
      '#description' => $this->t('Default URL is ":url".', [':url' => 'https://hcaptcha.com/1/api.js']),
      '#maxlength' => 200,
      '#required' => true,
      '#title' => $this->t('hCaptcha javascript resource URL'),
      '#type' => 'textfield',
    ];

    // Widget configurations.
    $form['widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget settings'),
      '#open' => true,
    ];
    $form['widget']['hcaptcha_theme'] = [
      '#default_value' => $config->get('widget.theme'),
      '#description' => $this->t('Defines which theme to use for hCaptcha.'),
      '#options' => [
        '' => $this->t('Light (default)'),
        'dark' => $this->t('Dark'),
      ],
      '#title' => $this->t('Theme'),
      '#type' => 'select',
    ];
    $form['widget']['hcaptcha_size'] = [
      '#default_value' => $config->get('widget.size'),
      '#description' => $this->t('The size of CAPTCHA to serve.'),
      '#options' => [
        '' => $this->t('Normal (default)'),
        'compact' => $this->t('Compact'),
      ],
      '#title' => $this->t('Size'),
      '#type' => 'select',
    ];
    $form['widget']['hcaptcha_tabindex'] = [
      '#default_value' => $config->get('widget.tabindex'),
      '#description' => $this->t('Set the <a href=":tabindex">tabindex</a> of the widget and challenge (Default = 0). If other elements in your page use tabindex, it should be set to make user navigation easier.', [':tabindex' => Url::fromUri('https://www.w3.org/TR/html4/interact/forms.html', ['fragment' => 'adef-tabindex'])->toString()]),
      '#maxlength' => 4,
      '#title' => $this->t('Tabindex'),
      '#type' => 'number',
      '#min' => -1,
    ];
    $form['widget']['hcaptcha_max_score'] = [
      '#default_value' => $config->get('widget.max_score'),
      '#description' => $this->t('Maximum acceptable score, 1.0 means a bot/clickfarm was definitely detected. <strong>Score-based verification requires an <a href=":hcaptcha_enterprise_url" target="_blank">enterprise hCaptcha account</a></strong>.', [
        ':hcaptcha_enterprise_url' => Url::fromUri('https://www.hcaptcha.com/enterprise')->toString(),
      ]),
      '#maxlength' => 4,
      '#title' => $this->t('Maximum Score'),
      '#type' => 'number',
      '#step' => 0.05,
      '#min' => 0,
      '#max' => 1.0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hcaptcha.settings');
    $config
      ->set('site_key', $form_state->getValue('hcaptcha_site_key'))
      ->set('secret_key', $form_state->getValue('hcaptcha_secret_key'))
      ->set('hcaptcha_src', $form_state->getValue('hcaptcha_src'))
      ->set('widget.theme', $form_state->getValue('hcaptcha_theme'))
      ->set('widget.size', $form_state->getValue('hcaptcha_size'))
      ->set('widget.tabindex', $form_state->getValue('hcaptcha_tabindex'))
      ->set('widget.max_score', $form_state->getValue('hcaptcha_max_score'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
