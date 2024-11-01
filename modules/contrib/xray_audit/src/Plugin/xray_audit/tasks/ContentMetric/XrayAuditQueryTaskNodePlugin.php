<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_node",
 *   label = @Translation("Node reports"),
 *   description = @Translation("Metrics about node entities."),
 *   group = "content_metric",
 *   sort = 1,
 *   local_task = 1,
 *   operations = {
 *      "number_node_type" = {
 *          "label" = "Grouped by type",
 *          "description" = "Number of Nodes grouped by type."
 *       },
 *       "number_node_type_lang" = {
 *          "label" = "Grouped by type and language",
 *          "description" = "Number of Nodes grouped by type and language."
 *       }
 *    },
 *   dependencies = {"node"}
 * )
 */
class XrayAuditQueryTaskNodePlugin extends XrayAuditQueryTaskPluginBase {

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
      case 'number_node_type':
        $data = $this->nodeByTypes();
        break;

      case 'number_node_type_lang':
        $data = $this->nodeTypesPerLanguage();
        break;
    }

    $this->pluginRepository->setCacheTagsInv($cid, $data, ['node_list']);
    return $data;
  }

  /**
   * Get data for operation "node_by_types".
   *
   * @return array
   *   Render array.
   */
  protected function nodeByTypes() {

    $alias_count = 'count';
    $headerTable = [
      $this->t('ID'),
      $this->t('Label'),
      $this->t('Total'),
      $this->t('Published'),
      $this->t('Unpublished'),
    ];
    $resultTable = [];

    // Get label of content types.
    $label_content_types = [];
    $types = $this->entityTypeManager->getStorage("node_type")->loadMultiple();
    foreach ($types as $key => $type) {
      $label_content_types[$key] = $type->label();
    }

    $bundles_counted = [];

    $query = $this->entityTypeManager->getStorage('node')->getAggregateQuery();

    $publish_bundle = $query->accessCheck(FALSE)
      ->condition('status', '1')
      ->groupBy('type')
      ->aggregate('nid', 'count', NULL, $alias_count)
      ->execute();
    /** @var array<string> $bundle */
    foreach ($publish_bundle as $bundle) {
      $bundles_counted[$bundle['type']]['publish'] = $bundle['count'];
    }

    $query = $this->entityTypeManager->getStorage('node')->getAggregateQuery();
    $unpublish_bundle = $query->accessCheck(FALSE)
      ->condition('status', '1', '!=')
      ->groupBy('type')
      ->aggregate('nid', 'count', NULL, $alias_count)
      ->execute();
    /** @var array<string> $bundle */
    foreach ($unpublish_bundle as $bundle) {
      $bundles_counted[$bundle['type']]['unpublish'] = $bundle['count'];
    }

    $total = 0;
    $total_publish = 0;
    $total_no_publish = 0;
    foreach ($label_content_types as $bundle => $content_type_label) {
      /** @var int $publish */
      $publish = $bundles_counted[$bundle]['publish'] ?? 0;
      /** @var int $unpublish */
      $unpublish = $bundles_counted[$bundle]['unpublish'] ?? 0;
      $resultTable[$bundle] = [
        'id' => $bundle,
        'label' => $content_type_label,
        'total' => $publish + $unpublish,
        'publish' => $publish,
        'no_publish' => $unpublish,
      ];
      $total += $publish + $unpublish;
      $total_publish += $publish;
      $total_no_publish += $unpublish;
    }
    $resultTable['total']['id'] = $this->t('TOTAL');
    $resultTable['total']['label'] = '---';
    $resultTable['total']['total'] = $total;
    $resultTable['total']['publish'] = $total_publish;
    $resultTable['total']['no-publish'] = $total_no_publish;

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

  /**
   * Get data for operation "node_by_types_language".
   *
   * @return array
   *   Render array.
   */
  protected function nodeTypesPerLanguage() {

    $alias = 'count';

    $headerTable = [
      $this->t('ID'),
      $this->t('Label'),
      $this->t('Langcode'),
      $this->t('Total'),
    ];
    $resultTable = [];

    // Get label of content types.
    $label = [];
    $types = $this->entityTypeManager->getStorage("node_type")->loadMultiple();
    foreach ($types as $key => $type) {
      $label[$key] = $type->label();
    }

    $query = $this->entityTypeManager->getStorage("node")->getAggregateQuery();
    $result = $query->accessCheck(FALSE)
      ->currentRevision()
      ->aggregate('nid', 'COUNT', NULL, $alias)
      ->groupBy('langcode')
      ->groupBy('type')
      ->sort('type')
      ->sort('langcode')
      ->execute();

    /** @var array<string> $row */
    foreach ($result as $row) {
      $resultTable[] = [
        'id' => $row['type'],
        'label' => $label[$row['type']],
        'langcode' => $row['langcode'],
        'total' => $row['count'] ?? 0,
      ];
    }

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

}
