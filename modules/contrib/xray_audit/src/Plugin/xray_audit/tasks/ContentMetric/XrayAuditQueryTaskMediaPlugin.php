<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_media",
 *   label = @Translation("Media reports"),
 *   description = @Translation("Reports on the Medias of this site"),
 *   group = "content_metric",
 *   sort = 3,
 *   operations = {
 *      "number_media_type" = {
 *          "label" = "Medias grouped by type",
 *          "description" = "Number of Medias grouped by type."
 *       },
 *    },
 *   dependencies = {"media"}
 * )
 */
class XrayAuditQueryTaskMediaPlugin extends XrayAuditQueryTaskPluginBase {

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
      case 'number_media_type':
        $data = $this->mediasTypes();
        break;
    }

    $this->pluginRepository->setCacheTagsInv($cid, $data, ['media_list']);
    return $data;
  }

  /**
   * Media.
   *
   * @return array
   *   Data.
   */
  public function mediasTypes() {
    $headerTable = [
      $this->t('ID'),
      $this->t('Label'),
      $this->t('Count'),
    ];
    $resultTable = [];

    $query = $this->entityTypeManager->getStorage('media')->getAggregateQuery();
    $aliasCount = 'count';
    $result = $query->accessCheck(FALSE)
      ->currentRevision()
      ->groupBy('bundle')
      ->aggregate('mid', 'COUNT', NULL, $aliasCount)
      ->sort('bundle')
      ->execute();
    $result_processed = [];
    /** @var array<string> $row */
    foreach ($result as $row) {
      $result_processed[$row['bundle']] = $row['count'];
    }
    $medias = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

    foreach ($medias as $key => $media) {
      $resultTable[] = [
        $key,
        $media->label(),
        $result_processed[$key] ?? 0,
      ];
    }
    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

}
