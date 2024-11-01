<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentModel;

use Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric\XrayAuditQueryTaskPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "content_entities",
 *   label = @Translation("Content Entities reports"),
 *   description = @Translation("Reports on all the content entities of this site."),
 *   group = "content_model",
 *   sort = 1,
 *   operations = {
 *      "content_entity_types" = {
 *          "label" = "Content entity  types",
 *          "description" = "List of the content entity types of this site (core, contrib and custom)."
 *       }
 *   }
 * )
 */
class XrayAuditQueryTaskContentEntitiesPlugin extends XrayAuditQueryTaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'content_entity_types':
        return $this->contentEntities();
    }
    return [];
  }

  /**
   * Get data for operation "content_entities".
   *
   * @return array
   *   Data.
   */
  protected function contentEntities() {

    $headerTable = [
      $this->t('id'),
      $this->t('Group'),
      $this->t('Provider'),
      $this->t('Class'),
    ];
    $resultTable = [];
    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $key => $definition) {
      $group = $definition->getGroup();
      if ($group !== 'content') {
        continue;
      }
      $resultTable[$key] = [
        'key' => $key,
        'group' => $group,
        'provider' => $definition->getProvider(),
        'class' => $definition->getClass(),
      ];
    }

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

}
