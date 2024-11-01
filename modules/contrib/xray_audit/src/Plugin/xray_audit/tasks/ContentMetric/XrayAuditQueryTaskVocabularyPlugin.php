<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_vocabulary",
 *   label = @Translation("Taxonomy Terms reports"),
 *   description = @Translation("Metrics about Vocabularies."),
 *   group = "content_metric",
 *   sort = 3,
 *   local_task = 1,
 *   operations = {
 *       "number_term_type" = {
 *          "label" = "Terms grouped by type",
 *          "description" = "Number of Terms grouped by type."
 *       },
 *      "number_term_type_lang" = {
 *          "label" = "Grouped by type and language",
 *          "description" = "Number of Terms grouped by type and language."
 *       }
 *    },
 *   dependencies = {"taxonomy"}
 *
 * )
 */
class XrayAuditQueryTaskVocabularyPlugin extends XrayAuditQueryTaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    $cid = $this->getPluginId() . ':' . $operation;

    $data = $this->pluginRepository->getCachedData($cid);
    if (!empty($data) && is_array($data)) {
      return $data;
    }

    switch ($operation) {
      case 'number_term_type':
        $data = $this->vocabulariesCount();
        break;

      case 'number_term_type_lang':
        $data = $this->vocabulariesCountPerLanguage();
        break;
    }

    $this->pluginRepository->setCacheTagsInv($cid, $data, ['taxonomy_term_list', 'taxonomy_vocabulary_list']);
    return $data;
  }

  /**
   * Vocabularies count.
   *
   * @return array
   *   Render array.
   */
  public function vocabulariesCount() {
    $headerTable = [
      $this->t('VID'),
      $this->t('Count'),
    ];
    $resultTable = [];
    $alias = 'count';

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $result = $storage
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->currentRevision()
      ->groupBy('vid')
      ->aggregate('tid', 'COUNT', NULL, $alias)
      ->sort('vid')
      ->execute();

    /** @var mixed[] $row */
    foreach ($result as $row) {
      if (empty($row['vid'])) {
        continue;
      }
      $resultTable[] = [$row['vid'], $row['count']];
    }
    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

  /**
   * Vocabularies count per language.
   *
   * @return array
   *   Render array.
   */
  public function vocabulariesCountPerLanguage() {
    $headerTable = [
      $this->t('VID'),
      $this->t('Langcode'),
      $this->t('Count'),
    ];
    $resultTable = [];
    $alias = 'count';

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $result = $storage
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->currentRevision()
      ->groupBy('vid')
      ->groupBy('langcode')
      ->aggregate('tid', 'COUNT', NULL, $alias)
      ->sort('vid')
      ->sort('langcode')
      ->execute();
    /** @var mixed[] $row */
    foreach ($result as $row) {
      $resultTable[] = [$row['vid'], $row['langcode'], $row['count']];
    }
    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

}
