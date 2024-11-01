<?php

namespace Drupal\xray_audit\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Service that build the paragraph usage map.
 */
class ParagraphUsageMap {

  /**
   * The xray_audit.entity_architecture service.
   *
   * @var \Drupal\xray_audit\Services\EntityArchitectureInterface
   */
  protected $entityArchitecture;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Storage tree.
   *
   * @var \Drupal\xray_audit\Services\StorageTree
   */
  static public $storageTree;

  /**
   * Storage.
   *
   * @var mixed[]
   */
  static public $storage;

  /**
   * Constructs an EntityUseParagraph object.
   *
   * @param \Drupal\xray_audit\Services\EntityArchitectureInterface $entity_architecture
   *   The xray_audit.entity_architecture service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Service Entity Type Manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Connection.
   */
  public function __construct(EntityArchitectureInterface $entity_architecture, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityArchitecture = $entity_architecture;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Get the storage tree.
   *
   * @return \Drupal\xray_audit\Services\StorageTree
   *   Storage tree.
   */
  public function getTreeMap() {
    if (!static::$storageTree instanceof StorageTree) {
      $this->buildParagraphTree();
    }
    return static::$storageTree;
  }

  /**
   * Set storage tree.
   */
  protected function buildParagraphTree() {
    $root = new StorageNode('', '');
    $entities = array_keys($this->getFieldDefinitions());
    if (!is_array($entities)) {
      return NULL;
    }
    foreach ($entities as $entity) {

      if ($entity === 'paragraph') {
        continue;
      }

      $node = new StorageNode($entity, 'all');
      $this->queryGetFields($entity, NULL, NULL, $node);
      $root->addChildren($node);

    }
    static::$storageTree = new StorageTree();
    static::$storageTree->setRoot($root);
  }

  /**
   * Get the fields to build the query and call the query.
   *
   * @param string $entity
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param mixed $subquery_parent
   *   The subquery.
   * @param mixed $node
   *   Node.
   */
  protected function queryGetFields(string $entity, string $bundle = NULL, $subquery_parent = NULL, $node = NULL) {
    $definition = $this->getFieldDefinitions();

    // Check if $definition[$entity]['paragraph_fields'] is set and is an array.
    $fields = isset($definition[$entity]['paragraph_fields']) && is_array($definition[$entity]['paragraph_fields'])
              ? $definition[$entity]['paragraph_fields']
              : [];

    // Fields for a bundle.
    if ($bundle) {
      // Get the fields installed in this bundle.
      $fields = array_filter($fields, function ($field) use ($bundle) {
        return in_array($bundle, $field['field_installed_in_bundles']);
      });

      if (empty($fields)) {
        return NULL;
      }
    }

    foreach ($fields as $field_definition) {
      $this->queryGetParagraphs($entity, $field_definition, $subquery_parent, NULL, $node);
    }
  }

  /**
   * Build the query and retrieve the results.
   *
   * @param string $entity
   *   The entity type.
   * @param array $field_definition
   *   The field definition.
   * @param mixed|null $subquery_parent
   *   The subquery parent.
   * @param string|null $paragraph_bundle_target
   *   The bundle target.
   * @param mixed|null $node
   *   The node.
   */
  protected function queryGetParagraphs(string $entity, array $field_definition, $subquery_parent = NULL, string $paragraph_bundle_target = NULL, $node = NULL) {
    $field_table = $field_definition['storage_definition']['table_name'];
    $field_table_column = $field_definition['storage_definition']['column_name'];

    $basic_query = $this->buildQueryObjectToGetParagraphs($field_table, $field_table_column, $paragraph_bundle_target, $subquery_parent);
    $number_of_types = $this->countParagraphsTypes(clone $basic_query);

    if (empty($number_of_types)) {
      return NULL;
    }

    $entity = 'paragraph';

    foreach ($number_of_types as $type) {
      $node_child = new StorageNode($entity, $type->bundle);
      $node_child->sum($type->count);
      $node->addChildren($node_child);

      $subquery_parent = $this->getParagraphEntities(clone $basic_query);
      $subquery_parent->condition('p.type', $type->bundle);
      $this->queryGetFields($entity, $type->bundle, $subquery_parent, $node_child);
    }
  }

  /**
   * Alter the query to get the paragraphs.
   *
   * @param mixed $query
   *   The query.
   *
   * @return mixed
   *   The results.
   */
  protected function countParagraphsTypes($query) {
    $query->groupBy('p.type');
    $query->addField('p', 'type', 'bundle');
    $query->addExpression('COUNT(p.type)', 'count');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * Later the basic query to get the ides.
   *
   * @param mixed $query
   *   The query object.
   *
   * @return mixed
   *   The later object.
   */
  protected function getParagraphEntities($query) {
    $query->addField('p', 'id');
    return $query;
  }

  /**
   * Get the paragraphs related to parent entities.
   *
   * Return only the paragraphs entities that have a relation with a
   * parent entity.
   * For that find paragraphs in the table of the field that set the relation.
   * In the subquery get the parents that have an active relation with other
   * parent.
   *
   * @param string $field_table
   *   The table name of the field.
   * @param string $field_table_column
   *   The column name with the value of the paragraph id.
   * @param string $paragraph_bundle_target
   *   The paragraph bundles we are looking for.
   * @param mixed $parent_subquery
   *   The active parents that can have a relation with the bundle paragraph.
   *
   * @return mixed
   *   The query object.
   */
  protected function buildQueryObjectToGetParagraphs(string $field_table, string $field_table_column, string $paragraph_bundle_target = NULL, $parent_subquery = NULL) {
    $select = $this->database->select($field_table, 'tbl');
    $select->join('paragraphs_item_field_data', 'p', 'tbl.' . $field_table_column . ' = p.id');

    if ($paragraph_bundle_target) {
      $select->condition('p.type', $paragraph_bundle_target);
    }
    if ($parent_subquery) {
      $select->condition('tbl.entity_id', $parent_subquery, 'IN');
    }
    return $select;
  }

  /**
   * Get the fields that reference paragraphs.
   *
   * @return array
   *   Definition of content entities.
   */
  public function getFieldDefinitions(): array {
    if (!isset(static::$storage['field_definition'])) {
      $this->setFieldDefinitions();
    }
    return static::$storage['field_definition'];
  }

  /**
   * Set in storage the fields in content and the target bundle.
   */
  protected function setFieldDefinitions(): void {
    $data = [];
    $content_entities = $this->entityArchitecture->getContentEntitiesInfo();

    foreach ($content_entities as $content_entity) {
      if (empty($content_entity['bundles'])) {
        continue;
      }
      $bundles = $content_entity['bundles'];
      $bundle_data = [];
      $processed_field_definition = [];

      foreach ($bundles as $bundle) {
        $bundle_data[$bundle['machine_name']] = [
          'machine_name' => $bundle['machine_name'],
          'label' => $bundle['label'],
        ];
        $fields = $this->entityArchitecture->getEntityFieldData(
          $content_entity['machine_name'],
          $bundle['machine_name']
        );
        $this->processFields($processed_field_definition, $fields);
      }

      if (empty($processed_field_definition)) {
        continue;
      }
      $data[$content_entity['machine_name']] = [
        'paragraph_fields' => $processed_field_definition,
        'bundle_data' => $bundle_data,
      ];
    }

    static::$storage['field_definition'] = $data;
  }

  /**
   * Process the fields of a bundle.
   *
   * @param array $definition_fields
   *   Processed Definition of fields.
   * @param array $fields
   *   Fields to process.
   */
  protected function processFields(array &$definition_fields, array $fields): void {
    foreach ($fields as $field) {
      $this->processFieldReferenceParagraph($field, $definition_fields);
    }
  }

  /**
   * Process field data for a field that is a reference to paragraphs.
   *
   * @param array $field
   *   Field definition.
   * @param array $definition_fields
   *   Processed Definition of fields.
   */
  protected function processFieldReferenceParagraph(array $field, array &$definition_fields): bool {
    $entity_type = $field['entity'];
    $bundle = $field['bundle'];
    $field_name = $field['name'];

    // Check ih the field is a reference to paragraphs.
    if ($field['type'] !== 'entity_reference_revisions') {
      return FALSE;
    }
    if (!isset($field['settings']['handler'])
      || $field['settings']['handler'] !== 'default:paragraph') {
      return FALSE;
    }

    // Check if there is already a storage data isset of the field.
    if (!isset($definition_fields[$field_name])) {
      $storage_data = $this->getStorageDefinition($entity_type, $field_name);
      if ($storage_data === NULL) {
        return FALSE;
      }
      $definition_fields[$field_name] = [];
      $definition_fields[$field_name]['storage_definition'] = $storage_data;
      $definition_fields[$field_name]['field_installed_in_bundles'] = [];
      $definition_fields[$field_name]['data_for_bundle'] = [];
    }

    $target_entity_bundles = [];
    if (!empty($field['settings']["handler_settings"]['target_bundles'])) {
      $target_entity_bundles = $field['settings']["handler_settings"]['target_bundles'];
    }

    $definition_fields[$field_name]['data_for_bundle'][$bundle] = [
      'paragraph_target' => $target_entity_bundles,
      'field_definition' => $field,
    ];
    $definition_fields[$field_name]['storage_definition']['entity_target_bundles'] = array_unique(array_merge($definition_fields[$field_name]['storage_definition']['entity_target_bundles'], $target_entity_bundles));
    $definition_fields[$field_name]['field_installed_in_bundles'][$bundle] = $bundle;

    return TRUE;
  }

  /**
   * Get the storage definition of a field.
   *
   * @param string $entity
   *   Entity.
   * @param string $field_name
   *   Field name.
   *
   * @return array|null
   *   Array with the data or null.
   */
  protected function getStorageDefinition(string $entity, string $field_name): ?array {

    /** @var  \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
    $field_storage_definition = $this->entityTypeManager->getStorage('field_storage_config')->load("{$entity}.{$field_name}");

    if (!$field_storage_definition instanceof FieldStorageDefinitionInterface) {
      return NULL;
    }

    $storage_definition = $this->entityTypeManager->getStorage($entity);
    if (!method_exists($storage_definition, 'getTableMapping')) {
      return NULL;
    }

    /** @var  \Drupal\Core\Entity\Sql\TableMappingInterface $entity_table_mapping_table */
    $entity_table_mapping_table = $storage_definition->getTableMapping();

    if (!$entity_table_mapping_table instanceof TableMappingInterface) {
      return NULL;
    }

    $field_table = $entity_table_mapping_table->getFieldTableName($field_name);
    $field_value_column = $entity_table_mapping_table->getFieldColumnName($field_storage_definition, $field_storage_definition->getMainPropertyName() ?? '');

    return [
      'table_name' => $field_table,
      'column_name' => $field_value_column,
      'field_storage_definition' => $field_storage_definition,
      'entity_target_bundles' => [],
    ];

  }

}
