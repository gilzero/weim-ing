<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configuring Module Builder.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_builder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'module_builder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('module_builder.settings');

    $form['data_directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Module builder data directory'),
      '#field_prefix' => 'public://',
      '#default_value' => $config->get('data_directory'),
      '#description' => $this->t("The location to store Module Builder's processed data within the site's files directory."),
      '#required' => TRUE,
    );

    $form['generator_settings_module'] = [
      '#type' => 'details',
      '#title' => $this->t("Module generation settings"),
      '#description' => $this->t("These settings apply to all generated module code."),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $data = \Drupal::service('module_builder.drupal_code_builder')->getTask('Configuration')->getConfigurationData('module');

    $module_config = $config->get('generator_settings.module');
    $data->import($module_config ?? []);

    // We can be quite simplistic here, as (so far!) configuration from the
    // root component is just one complex data item with simple properties.
    foreach ($data as $key => $setting) {
      $setting_element = [];

      if ($setting->getType() == 'boolean') {
        $element_type = 'checkbox';
      }
      elseif ($setting->hasOptions()) {
        $element_type = 'radios';

        $setting_element['#options'] = array_map(fn ($option) => $option->getLabel(), $setting->getOptions());
      }
      else {
        $element_type = 'textfield';
      }

      $setting_element += [
        '#type' => $element_type,
        '#title' => $setting->getLabel(),
        '#description' => $setting->getDescription(),
        '#default_value' => $setting->value,
      ];

      $form['generator_settings_module'][$key] = $setting_element;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('module_builder.settings')
      ->set('data_directory', $form_state->getValue('data_directory'))
      ->set('generator_settings.module', $form_state->getValue('generator_settings_module'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
