<?php

namespace Drupal\xray_audit_insight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for settings form.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The module settings.
   */
  const MODULE_SETTINGS = 'xray_audit_insight.settings';

  /**
   * The plugin manager.
   *
   * @var \Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form_instance = new static($container->get('config.factory'), $container->get('config.typed'));
    $form_instance->pluginManager = $container->get('plugin_manager.xray_audit_insight');
    return $form_instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::MODULE_SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::MODULE_SETTINGS;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::MODULE_SETTINGS);
    $excluded_insights = $config->get('excluded_insights') ?? [];

    $form['insight_switch'] = [
      '#type' => 'container',
    ];
    $form['insight_switch']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => $this->t('Exclusion of insights'),
    ];
    $form['insight_switch']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select the insights that you do not want to be displayed in the Status Report.'),
    ];

    $insights = $this->getInsights();
    foreach ($insights as $insight) {
      $form['insight_switch'][$insight['id']] = [
        '#type' => 'checkbox',
        '#title' => $insight['label'] ?? '',
        '#default_value' => in_array($insight['id'], $excluded_insights),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config_object = $this->config(static::MODULE_SETTINGS);
    $insights = $this->getInsights();
    $excluded_insights = [];

    foreach ($insights as $insight) {
      if ((bool) $form_state->getValue($insight['id'])) {
        $excluded_insights[] = $insight['id'];
      }
    }
    $config_object->set('excluded_insights', $excluded_insights);
    $config_object->save();
  }

  /**
   * Get the insights.
   *
   * @return array
   *   The insights.
   */
  protected function getInsights(): array {
    $insights = [];
    $insight_definitions = $this->pluginManager->getDefinitions();
    foreach ($insight_definitions as $plugin_id => $insight) {
      $insights[$plugin_id] = [
        'id' => $plugin_id,
        'label' => $insight['label'],
      ];
    }
    return $insights;
  }

}
