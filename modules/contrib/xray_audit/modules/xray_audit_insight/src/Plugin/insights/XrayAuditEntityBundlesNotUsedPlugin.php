<?php

namespace Drupal\xray_audit_insight\Plugin\insights;

use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditInsightPlugin (
 *   id = "bundle_not_used",
 *   label = @Translation("Entities Bundles Not Used"),
 *   description = @Translation("Entity Bundles not used"),
 *   sort = 1
 * )
 */
class XrayAuditEntityBundlesNotUsedPlugin extends XrayAuditInsightPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getInsights(): array {
    $values = [];

    // Nodes.
    $node_value = $this->getNotUsedEntityBundles('queries_data_node', 'number_node_type', 'total', 'label');
    if ($node_value !== NULL) {
      $values['node'] = [
        'count' => count($node_value),
        'bundles_not_used' => count($node_value) > 0,
        'bundles' => $node_value,
        'entity_label' => 'Node',
        'report_path' => $this->getPathReport('queries_data_node', 'number_node_type'),
      ];
    }

    // Paragraphs.
    $paragraph_value = $this->getNotUsedEntityBundles('queries_data_paragraphs', 'number_paragraphs_type', 2, 1);
    if ($paragraph_value !== NULL) {
      $values['paragraph'] = [
        'count' => count($paragraph_value),
        'bundles_not_used' => count($paragraph_value) > 0,
        'bundles' => $paragraph_value,
        'entity_label' => 'Paragraphs',
        'report_path' => $this->getPathReport('queries_data_paragraphs', 'number_paragraphs_type'),
      ];
    }

    // Paragraphs.
    $media_value = $this->getNotUsedEntityBundles('queries_data_media', 'number_media_type', 2, 1);
    if ($media_value !== NULL) {
      $values['media'] = [
        'count' => count($media_value),
        'bundles_not_used' => count($media_value) > 0,
        'bundles' => $media_value,
        'entity_label' => 'Media',
        'report_path' => $this->getPathReport('queries_data_media', 'number_media_type'),
      ];
    }

    // Vocabulary.
    $vocabulary_value = $this->getNotUsedEntityBundles('queries_data_vocabulary', 'number_term_type', 1, 0);
    if ($vocabulary_value !== NULL) {
      $values['vocabulary'] = [
        'count' => count($vocabulary_value),
        'bundles_not_used' => count($vocabulary_value) > 0,
        'bundles' => $vocabulary_value,
        'entity_label' => 'Vocabulary',
        'report_path' => $this->getPathReport('queries_data_vocabulary', 'number_term_type'),
      ];
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getInsightsForDrupalReport(): array {
    $cases = [];
    $insights = $this->getInsights();
    foreach ($insights as $entity_id => $insight) {

      if ($insight['bundles_not_used'] === FALSE) {
        continue;
      }

      $title = "<a href=\"{$insight['report_path']}\">{$insight['entity_label']}</a>:";
      $description = implode(', ', $insight['bundles']);

      $cases[$entity_id] = [];
      $cases[$entity_id]['title'] = [
        '#markup' => $title,
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      $cases[$entity_id]['description'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
      ];
      $cases[$entity_id]['description']['content'] = [
        '#markup' => $description,
      ];
    }

    $title = $this->t('Entities bundles usage');
    $value = '';
    $description = '';
    $severity = NULL;

    if (empty($cases)) {
      $value = $this->t("No content entity bundles that are not being used have been detected.");
    }
    else {
      $value = $this->t("The following entities have bundles that are not used.");
      $description = $this->renderer->renderPlain($cases);
      $severity = REQUIREMENT_WARNING;
    }

    return [
      'entities_bundles_not_used' =>
      $this->buildInsightForDrupalReport(
          $title,
          $value,
          $description,
          $severity),
    ];

  }

  /**
   * Get the result from one of the plugins.
   *
   * @param string $task_plugin_id
   *   The task plugin id.
   * @param string $operation
   *   The operation.
   * @param mixed $total_key
   *   The total key.
   * @param mixed $label_key
   *   The label key.
   *
   * @return array|null
   *   Results.
   */
  protected function getNotUsedEntityBundles(string $task_plugin_id, string $operation, $total_key, $label_key): ?array {
    $results = [];

    $task_plugin = $this->getInstancedPlugin($task_plugin_id, $operation);
    if (!$task_plugin instanceof XrayAuditTaskPluginInterface) {
      return NULL;
    }

    $data = $task_plugin->getDataOperationResult($operation);

    // Analyze the data.
    if (empty($data['results_table'])) {
      return $results;
    }

    foreach ($data['results_table'] as $bundle) {
      if ($bundle[$total_key] == 0) {
        $results[] = $bundle[$label_key];
      }
    }

    return $results;
  }

}
