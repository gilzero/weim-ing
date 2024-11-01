<?php

namespace Drupal\site_audit_report_entity\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\EntityViewsDataInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides Site Audit Report Data field handler.
 *
 * @ViewsField("site_audit_report_entity_data")
 *
 * @DCG
 * The plugin needs to be assigned to a specific table column through
 * hook_views_data() or hook_views_data_alter().
 * For non-existent columns (i.e. computed fields) you need to override
 * self::query() method.
 */
class ReportData extends FieldPluginBase implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['example'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['example'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example'),
      '#default_value' => $this->options['example'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = parent::render($values);
    // @DCG Modify or replace the rendered value here.
    return $value;
  }

  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    // TODO: Implement getViewsTableForEntityType() method.

  }

  /**
   * Gets views data service.
   *
   * @return \Drupal\views\ViewsData
   */
  public function getViewsData() {
    return;
  }
}
