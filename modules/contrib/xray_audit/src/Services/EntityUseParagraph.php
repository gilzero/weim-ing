<?php

namespace Drupal\xray_audit\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to get paragraphs use.
 */
class EntityUseParagraph extends EntityUseBase implements EntityUseInterface {

  const ENTITY_TYPE = 'paragraph';

  /**
   * Parent entity type.
   *
   * @var string
   */
  protected $parentEntityType;

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
   * Paragraph Usage Map.
   *
   * @var \Drupal\xray_audit\Services\ParagraphUsageMap
   */
  protected $paragraphUsageMap;

  /**
   * The storage of the service.
   *
   * @var mixed[]
   */
  static protected $storage = [];

  /**
   * Constructs an EntityUseParagraph object.
   *
   * @param \Drupal\xray_audit\Services\EntityArchitectureInterface $entity_architecture
   *   The xray_audit.entity_architecture service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Service Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService
   *   Service Bundle Info.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Service Config Factory.
   * @param \Drupal\xray_audit\Services\ParagraphUsageMap $paragraph_usage_map
   *   Service Paragraph Usage Map.
   */
  public function __construct(
    EntityArchitectureInterface $entity_architecture,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundleInfoService,
    ConfigFactoryInterface $config_factory,
    ParagraphUsageMap $paragraph_usage_map
  ) {
    parent::__construct($entity_architecture, $entity_type_manager, $bundleInfoService, $config_factory);
    $this->paragraphUsageMap = $paragraph_usage_map;
  }

  /**
   * {@inheritdoc}
   */
  public function initParameters(string $parent_entity_type, string $entity_bundle = NULL) {
    $this->parentEntityType = $parent_entity_type;
    $this->entityBundleSetExternally = $entity_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function countEntityUses(): ?array {
    $build_data = [];
    $site_name = $this->getSiteName();
    $paragraph_usage_tree = $this->paragraphUsageMap->getTreeMap();
    $usage_data = $paragraph_usage_tree->summaryDataByParagraph();
    $bundle_data = $this->paragraphUsageMap->getFieldDefinitions();
    $paragraph_bundle_data = $bundle_data['paragraph']['bundle_data'] ?? [];

    foreach ($paragraph_bundle_data as $paragraph_bundle) {
      $build_data[$paragraph_bundle['machine_name']] = [
        'entity' => EntityUseParagraph::ENTITY_TYPE,
        'bundle' => $paragraph_bundle['machine_name'],
        'label' => $paragraph_bundle['label'],
        'count' => 0,
        'site' => $site_name,
      ];
    }

    foreach ($usage_data as $usage) {
      $build_data[$usage['bundle']]['count'] = $usage['count'];
    }

    return $build_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityUsePlaces(): ?array {

    $this->countEntityUses();

    if ($this->checkParametersAreCorrect() === NULL) {
      return NULL;
    }

    $targetBundles = [];
    if (empty($this->entityBundleSetExternally)) {
      $targetBundles = $this->getTargetParagraphBundles();
    }
    else {
      $targetBundles = [$this->entityBundleSetExternally];
    }

    $final_result = [];

    $this->entityUsageData($targetBundles, $final_result, self::ENTITY_STATUS_PUBLISHED);
    $this->entityUsageData($targetBundles, $final_result, self::ENTITY_STATUS_UNPUBLISHED);

    return $final_result;
  }

  /**
   * Get the processed data about parent entities.
   *
   * @todo Try to refactor the logic using ParagraphUsageMap service.
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
      $this->entityBundle = $targetBundle;
      $query = $this->buildBasicQuery($status_entity);
      $query->range(0, self::LIMIT_QUERIES);
      $result = ($query === NULL) ? ['NONE'] : $query->execute();
      $final_result = array_merge($final_result, $this->processResults($this->parentEntityType, EntityUseParagraph::ENTITY_TYPE, $this->entityBundle, $result, $status_entity));
    }
  }

  /**
   * Check parameters.
   *
   * @return true|null
   *   True if the parameters are correct, null if not.
   *
   * @throws \Exception
   */
  protected function checkParametersAreCorrect(): ?bool {
    // Check parameters are correct.
    if (empty($this->parentEntityType)) {
      throw new \Exception('You must set the parent entity type and the paragraph bundle.');
    }
    // Check the paragraph bundle exists.
    if (!empty($this->entityBundle)) {
      if (!$this->checkEntityBundleExists(EntityUseParagraph::ENTITY_TYPE, $this->entityBundle)) {
        return NULL;
      }
    }
    return TRUE;
  }

  /**
   * Get the basic query object.
   *
   * The use definition in paragraphs is when is related to a published node.
   *
   * @param int $status_entity
   *   Status entity.
   *
   * @return mixed
   *   The query object.
   */
  protected function buildBasicQuery(int $status_entity = self::ENTITY_STATUS_IRRELEVANT) {

    // Build the key to storage.
    $key = "$this->entityBundle-$this->parentEntityType-query-" . (string) $status_entity;

    if (!isset(static::$storage[$key])) {
      $fields = $this->getFieldInContentReferenceToParagraphs();

      // If there are no fields, return null. There are not entities this case.
      if ($fields === []) {
        return NULL;
      }

      $query = $this->entityTypeManager->getStorage($this->parentEntityType)->getQuery();
      $query->accessCheck(FALSE);
      $orGroup = $query->orConditionGroup();
      foreach ($fields as $field) {
        $orGroup->condition($field['name'] . '.entity.type', $this->entityBundle);
      }

      switch ($status_entity) {
        case self::ENTITY_STATUS_PUBLISHED:
          $query->condition('status', 1);
          break;

        case self::ENTITY_STATUS_UNPUBLISHED:
          $query->condition('status', 0);
          break;
      }

      $query->condition($orGroup);
      static::$storage[$key] = $query;
    }
    return clone static::$storage[$key];
  }

  /**
   * Get the target bundles of the fields in content.
   *
   * @return array
   *   Fields of nodes that reference paragraphs.
   */
  protected function getTargetParagraphBundles() {
    $key_target_bundles = "$this->parentEntityType-target-paragraph-bundle";
    if (!isset(static::$storage[$key_target_bundles])) {
      $this->setInStorageFieldsAndTargetBundleInContent();
    }
    return static::$storage[$key_target_bundles];
  }

  /**
   * Get the fields in nodes that reference paragraphs.
   *
   * @return array
   *   Fields of nodes that reference paragraphs.
   */
  protected function getFieldInContentReferenceToParagraphs(): array {
    $key_fields = "$this->parentEntityType-fields";
    if (!isset(static::$storage[$key_fields])) {
      $this->setInStorageFieldsAndTargetBundleInContent();
    }
    return static::$storage[$key_fields];
  }

  /**
   * Set in storage the fields in content and the target bundle.
   */
  protected function setInStorageFieldsAndTargetBundleInContent(): void {
    $key_fields = "$this->parentEntityType-fields";
    $key_target_bundles = "$this->parentEntityType-target-paragraph-bundle";

    $entity_fields_referenced_paragraphs = [];
    $target_paragraphs = [];

    $content_entities = $this->entityArchitecture->getContentEntitiesInfo();
    $content_definition = $content_entities[$this->parentEntityType];
    foreach ($content_definition['bundles'] as $bundle) {
      $fields = $this->entityArchitecture->getEntityFieldData($this->parentEntityType, $bundle['machine_name']);
      $this->processFieldsOfBundle($entity_fields_referenced_paragraphs, $target_paragraphs, $fields);
    }

    static::$storage[$key_fields] = $entity_fields_referenced_paragraphs;
    static::$storage[$key_target_bundles] = $target_paragraphs;
  }

  /**
   * Process the fields of a bundle to get only the fields reference paragraphs.
   *
   * @param array $entity_fields_referenced_paragraphs
   *   Fields of nodes that reference paragraphs.
   * @param array $target_paragraphs
   *   Target paragraphs.
   * @param array $fields
   *   Fields of a bundle.
   */
  protected function processFieldsOfBundle(array &$entity_fields_referenced_paragraphs, array &$target_paragraphs, array $fields): void {
    foreach ($fields as $field) {
      if ($field['type'] !== 'entity_reference_revisions') {
        continue;
      }
      if (!isset($field['settings']['handler']) || $field['settings']['handler'] !== 'default:paragraph') {
        continue;
      }

      if (isset($field['settings']["handler_settings"]['target_bundles'])) {
        $target_paragraphs = array_unique(array_merge($target_paragraphs, $field['settings']["handler_settings"]['target_bundles']));
      }

      $entity_fields_referenced_paragraphs[$field['name']] = $field;
    }
  }

}
