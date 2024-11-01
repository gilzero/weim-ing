<?php

declare(strict_types = 1);

namespace Drupal\site_audit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_audit\Plugin\SiteAuditChecklistManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class SiteAuditConfigForm extends ConfigFormBase {


  /**
   * @var \Drupal\site_audit\Plugin\SiteAuditChecklistManager
   */
  protected $checklist_plugin_manager;

  /**
   * SiteAuditConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\site_audit\Plugin\SiteAuditChecklistManager $site_audit_checklist_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, SiteAuditChecklistManager $site_audit_checklist_manager) {
    parent::__construct($config_factory);
    $this->checklist_plugin_manager = $site_audit_checklist_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.site_audit_checklist')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_audit.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_audit_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $saved_options = $this->config('site_audit.settings')->get('reports');
    $checklists = $this->checklist_plugin_manager->getDefinitions();
    foreach ($checklists as $checklist) {
      $options[$checklist['id']] = $checklist['name'];
    }
    if (empty($saved_options)) {
      $saved_options = [];
    }
    $form['reports'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Site Reports'),
      '#description' => $this->t('Check the box to run any reports on the audit page. If no reports are selected then all reports will be run.'),
      '#options' => $options,
      '#default_value' => $saved_options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('site_audit.settings')->set('reports', $form_state->getValue('reports'))->save();
    parent::submitForm($form, $form_state);
  }

}
