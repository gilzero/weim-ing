<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentModel;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric\XrayAuditQueryTaskPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "vocabulary_content_model",
 *   label = @Translation("Vocabularies reports"),
 *   description = @Translation("Reports on the vocabularies (taxonomies) of this site."),
 *   group = "content_model",
 *   sort = 15,
 *   operations = {
 *      "vocabulary_description" = {
 *          "label" = "Vocabulary descriptions",
 *          "description" = ""
 *       }
 *    },
 *   dependencies = {"taxonomy"}
 * )
 */
class XrayAuditVocabularyPlugin extends XrayAuditQueryTaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'vocabulary_description':
        return $this->vocabulariesDescription();
    }
    return [];
  }

  /**
   * Vocabularies description.
   *
   * @return array
   *   Render array.
   */
  public function vocabulariesDescription() {
    $headerTable = [
      $this->t('Id'),
      $this->t('Label'),
      $this->t('Description'),
      $this->t('Langcode'),
    ];
    $resultTable = [];
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $key => $vocabulary) {
      $resultTable[] = [
        $key,
        $vocabulary->label(),
        $vocabulary->get('description'),
        $vocabulary->get('langcode'),
      ];
    }
    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

}
