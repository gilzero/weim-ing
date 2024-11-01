<?php

namespace Drupal\xray_audit\Services;

/**
 * Service to get node use.
 */
class EntityUseNode extends EntityUseBase implements EntityUseInterface {

  const ENTITY_TYPE = 'node';

  /**
   * Parent entity type.
   *
   * @var string
   */
  protected $parentEntityType = 'node';

  /**
   * Entity Bundle set externally.
   *
   * @var string|null
   */
  protected $entityBundleSetExternally;

  /**
   * Entity bundle.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * The storage of the service.
   *
   * @var mixed[]
   */
  static public $storage = [];

  /**
   * {@inheritdoc}
   */
  public function initParameters(string $parent_entity_type = 'node', string $entity_bundle = NULL) {
    $this->entityBundleSetExternally = $entity_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function countEntityUses(): ?array {
    $entity_bundles = [];
    // Check the paragraph bundle exists.
    if (!empty($this->entityBundleSetExternally)) {
      if (!$this->checkEntityBundleExists(EntityUseNode::ENTITY_TYPE, $this->entityBundleSetExternally)) {
        return [];
      }
      $entity_bundles = [$this->entityBundleSetExternally];
    }
    else {
      $entity_bundles = $this->getAllNodeBundles();
    }

    $final_result = [];
    $site_name = $this->getSiteName();

    foreach ($entity_bundles as $entity_bundle) {
      $query = $this->buildBasicQuery($entity_bundle, self::ENTITY_STATUS_PUBLISHED);
      $result = '';
      if ($query === NULL) {
        $result = 0;
      }
      else {
        $query->count();
        $result = $query->execute();
      }
      $final_result = array_merge(
        $final_result,
        [[
          'parent_entity_type' => $this->parentEntityType,
          'entity' => EntityUseNode::ENTITY_TYPE,
          'bundle' => $entity_bundle,
          'count' => $result,
          'site' => $site_name,
        ],
        ]
      );
    }
    return $final_result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityUsePlaces(): array {
    $entity_bundles = [];
    // Check the paragraph bundle exists.
    if (!empty($this->entityBundleSetExternally)) {
      if (!$this->checkEntityBundleExists(EntityUseNode::ENTITY_TYPE, $this->entityBundleSetExternally)) {
        return [];
      }
      $entity_bundles = [$this->entityBundleSetExternally];
    }
    else {
      $entity_bundles = $this->getAllNodeBundles();
    }

    $final_result = [];

    $this->entityUsageData($entity_bundles, $final_result, self::ENTITY_STATUS_PUBLISHED);
    $this->entityUsageData($entity_bundles, $final_result, self::ENTITY_STATUS_UNPUBLISHED);

    return $final_result;
  }

  /**
   * Get the processed data about parent entities.
   *
   * @param array $targetBundles
   *   Paragraph target bundles.
   * @param array $final_result
   *   Processed $status_entity.
   * @param int $status_entity
   *   Status of parent entity.
   */
  protected function entityUsageData(array $targetBundles, array &$final_result, $status_entity = self::ENTITY_STATUS_IRRELEVANT): void {
    foreach ($targetBundles as $targetBundle) {
      $query = $this->buildBasicQuery($targetBundle, $status_entity);
      $query->range(0, self::LIMIT_QUERIES);
      $result = ($query === NULL) ? ['NONE'] : $query->execute();
      $final_result = array_merge($final_result, $this->processResults($this->parentEntityType, EntityUseNode::ENTITY_TYPE, $targetBundle, $result, $status_entity));
    }
  }

  /**
   * Get the basic query object.
   *
   * The use definition in nodes is when a node is published.
   *
   * @param string $entity_bundle
   *   The entity bundle.
   * @param int $status_entity
   *   Status entity.
   *
   * @return mixed
   *   The query object.
   */
  protected function buildBasicQuery(string $entity_bundle, int $status_entity = self::ENTITY_STATUS_PUBLISHED) {
    // Build the key to storage.
    $key = "$entity_bundle-query-" . (string) $status_entity;

    if (!isset(static::$storage[$key])) {
      $query = $this->entityTypeManager->getStorage($this->parentEntityType)->getQuery();
      $query->accessCheck(FALSE);
      $query->condition('type', $entity_bundle);

      switch ($status_entity) {
        case self::ENTITY_STATUS_PUBLISHED:
          $query->condition('status', 1);
          break;

        case self::ENTITY_STATUS_UNPUBLISHED:
          $query->condition('status', 0);
          break;
      }

      static::$storage[$key] = $query;
    }
    return clone static::$storage[$key];
  }

  /**
   * Get all the node bundles.
   *
   * @return array
   *   Node bundles.
   */
  protected function getAllNodeBundles(): array {
    $node_bundles = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      $node_bundles[] = $node_type->id();
    }
    return $node_bundles;
  }

}
