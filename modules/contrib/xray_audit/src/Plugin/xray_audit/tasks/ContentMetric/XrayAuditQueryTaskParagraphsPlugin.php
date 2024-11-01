<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\xray_audit\Services\EntityUseInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_paragraphs",
 *   label = @Translation("Paragraphs reports"),
 *   description = @Translation("Metrics about paragraphs entities."),
 *   group = "content_metric",
 *   sort = 2,
 *   local_task = 1,
 *   operations = {
 *     "number_paragraphs_type" = {
 *          "label" = "Grouped by type",
 *          "description" = "Number of Paragraphs grouped by type."
 *       },
 *     "number_paragraphs_lang" = {
 *          "label" = "Grouped by language",
 *           "description" = "Number of Paragraphs grouped by language."
 *       },
 *      "number_paragraphs_hierarchy" = {
 *          "label" = "Grouped hierarchically by type",
 *          "description" = "Number of Paragraphs grouped hierarchically by type."
 *       },
 *      "paragraphs_usage" = {
 *          "label" = "Paragraphs Usage",
 *          "description" = "Paragraphs Usage.",
 *          "not_show" = true
 *       },
 *    },
 *   dependencies = {"paragraphs"},
 *   install = "paragraphsInstallActions",
 *   uninstall = "paragraphsUninstallActions"
 * )
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
final class XrayAuditQueryTaskParagraphsPlugin extends XrayAuditQueryTaskPluginBase {

  const XRA_PARAGRAPH_TEMPORARY_TABLE_NAME = 'xra_paragraphs_hierarchy_tmp';

  /**
   * Service "xray_audit.entity_use_paragraph".
   *
   * @var \Drupal\xray_audit\Services\EntityUseInterface
   */
  protected $serviceEntityUseParagraph;

  /**
   * Service "request_stack".
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Service "state".
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Database connection.
   * @param \Drupal\xray_audit\Services\EntityUseInterface $entity_use_paragraph
   *   Service "xray_audit.entity_use_paragraph".
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Service "request_stack".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $xray_audit_plugin_repository
   *   Service "xray_audit.plugin_repository".
   * @param \Drupal\Core\State\StateInterface $state
   *   Service "state".
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LanguageManagerInterface $language_manager,
    EntityUseInterface $entity_use_paragraph,
    RequestStack $request_stack,
    PluginRepositoryInterface $xray_audit_plugin_repository,
    StateInterface $state
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $database, $language_manager, $xray_audit_plugin_repository);
    $this->serviceEntityUseParagraph = $entity_use_paragraph;
    $this->requestStack = $request_stack;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('xray_audit.entity_use_paragraph'),
      $container->get('request_stack'),
      $container->get('xray_audit.plugin_repository'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = ''): array {
    $data = [];
    $cid = $this->getPluginId() . ':' . $operation;
    $cache_tags = ['paragraph_list'];

    switch ($operation) {
      case 'number_paragraphs_type':
        $data = $this->getDataOperationResultCached($cid, 'paragraphsTypes', $cache_tags);
        break;

      case 'number_paragraphs_hierarchy':
        $data = $this->getDataOperationResultCached($cid, 'paragraphsTypesHierarchy', $cache_tags);
        break;

      case 'paragraphs_usage':
        $parameters = $this->getQueryParametersParagraphUsagePlace();
        if ($parameters === NULL) {
          return [];
        }
        $cache_tags[] = 'node_list';
        $cid .= ':' . $parameters['parent'] . ':' . $parameters['bundle'];
        $data = $this->getDataOperationResultCached($cid, 'paragraphsUsePlace', $cache_tags);
        break;

      case 'number_paragraphs_lang':
        $data = $this->getDataOperationResultCached($cid, 'paragraphsItemsCountByLanguage', $cache_tags);
        break;
    }

    return $data;
  }

  /**
   * Apply cache layer.
   *
   * @param string $cid
   *   Cid.
   * @param string $method
   *   Method to get the data.
   * @param array $cache_tags
   *   Cache tags.
   *
   * @return array
   *   Data.
   */
  protected function getDataOperationResultCached(string $cid, string $method, array $cache_tags): array {

    $data = $this->pluginRepository->getCachedData($cid);
    if (!empty($data) && is_array($data)) {
      return $data;
    }

    $data = $this->{$method}();

    $this->pluginRepository->setCacheTagsInv($cid, $data, $cache_tags);
    return $data;
  }

  /**
   * Get data of Paragraph types.
   *
   * @return array
   *   Render array.
   */
  public function paragraphsTypes(): array {
    $headerTable = [
      $this->t('ID'),
      $this->t('Label'),
      $this->t('Count'),
    ];
    $resultTable = [];

    $storage_type = $this->entityTypeManager->getStorage('paragraphs_type');
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $alias_count = 'count';

    $result = $storage
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->currentRevision()
      ->aggregate('id', 'count', NULL, $alias_count)
      ->groupBy('type')
      ->sort('type')
      ->execute();

    // Prepare results.
    $result_processed = [];
    /** @var mixed[] $row */
    foreach ($result as $row) {
      $result_processed[$row['type']] = $row['count'];
    }
    $total = 0;

    $paragraph_types = $storage_type->loadMultiple();
    foreach ($paragraph_types as $key => $paragraph_type) {
      $resultTable[] = [
        $key,
        $paragraph_type->label(),
        ($result_processed[$key] ?? 0),
      ];
      $total = $total + ($result_processed[$key] ?? 0);
    }

    $resultTable[] = [
      '',
      $this->t('Total'),
      $total,
    ];

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

  /**
   * Get data of Paragraph types ordered hierarchically.
   *
   * @return array
   *   Render array.
   */
  public function paragraphsTypesHierarchy(): array {
    $this->paragraphsHierarchyCreateTemporaryTable();

    $headerTable = [
      $this->t('Level'),
      $this->t('Parent'),
      '',
      $this->t('Count'),
      $this->t('Parent list'),
    ];
    $language_by_default = $this->languageManager->getDefaultLanguage()->getId();

    $query = $this->database->select(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME, 'tmp')
      ->fields('tmp', ['level', 'parent_bundle', 'type']);
    $query->addExpression('COUNT(tmp.id)', 'count');
    $query = $query->condition('tmp.langcode', $language_by_default)
      ->groupBy('tmp.hierarchy_id')
      ->groupBy('tmp.parent_bundle')
      ->groupBy('tmp.level')
      ->groupBy('tmp.type')
      ->orderBy('tmp.hierarchy_id')
      ->execute();

    $results = [];
    if ($query instanceof StatementInterface) {
      $results = $query->fetchAll();
    }

    // Prepare results.
    $resultTable = [];
    $paragraph_types = $this->entityTypeManager->getStorage('paragraphs_type')->loadMultiple();
    $total = 0;

    $parent_list_link_base = "<a href='@url' target='_blank'>See usage</a>";

    foreach ($results as $result) {
      $parent_list_link = '';
      $result = (array) $result;
      $indent_string = $result['level'] != '1' ? str_repeat(' ', $result['level'] - 1) . '- ' : NULL;
      $parent_label = $result['level'] == '1' ? $result['parent_bundle'] : NULL;
      if ($result['level'] == '1') {
        $parent_list_url = $this->pluginRepository->getTaskPageOperationFromIdOperation(
          'paragraphs_usage',
          [
            'parent' => $result['parent_bundle'],
            'bundle' => $result['type'],
          ])->toString(); 
        $parent_list_link = new FormattableMarkup($parent_list_link_base, ['@url' => $parent_list_url]);
      }
      $paragraph_type = $paragraph_types[$result['type']] ?? NULL;
      $label = ($paragraph_type) ? $paragraph_type->label() : $result['type'];
      $resultTable[] = [
        $result['level'],
        $parent_label,
        $indent_string . $label . ' (' . $result['type'] . ')',
        $result['count'],
        $parent_list_link,
      ];
      $total = $total + $result['count'];
    }

    $resultTable[] = [
      '',
      '',
      $this->t('Total'),
      $total,
    ];

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

  /**
   * Paragraphs items count by language.
   *
   * @return array
   *   Render array.
   */
  public function paragraphsItemsCountByLanguage(): array {

    $headerTable = [
      $this->t('Label'),
      $this->t('Type'),
      $this->t('Langcode'),
      $this->t('Total'),
    ];
    $resultTable = [];

    $storage_type = $this->entityTypeManager->getStorage('paragraphs_type');
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $alias_count = 'count';

    // Get label of content types.
    $label = [];
    $types = $storage_type->loadMultiple();
    foreach ($types as $key => $type) {
      $label[$key] = $type->label();
    }

    $query = $storage->getAggregateQuery();
    $result = $query->accessCheck(FALSE)
      ->currentRevision()
      ->aggregate('id', 'COUNT', NULL, $alias_count)
      ->groupBy('langcode')
      ->groupBy('type')
      ->sort('type')
      ->sort('langcode')
      ->execute();

    /** @var mixed[] $row */
    foreach ($result as $row) {
      $resultTable[] = [
        'label' => $label[$row['type']] ?? $row['type'],
        'id' => $row['type'],
        'langcode' => $row['langcode'],
        'total' => $row['count'] ?? 0,
      ];
    }

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

  /**
   * Create/update the paragraphs Hierarchy temporally table.
   */
  protected function paragraphsHierarchyCreateTemporaryTable(): void {
    $state_service = $this->state;
    $last_execution = $state_service->get('xray_audit.paragraphs_hierarchy_temporary_table_creation', 0);

    if ($last_execution > strtotime("-1 day")) {
      return;
    }

    $this->database->truncate(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME)->execute();

    $select = $this->database->select('paragraphs_item_field_data')
      ->fields('paragraphs_item_field_data', [
        'id',
        'parent_id',
        'type',
        'langcode',
        'parent_type',
      ])
      ->condition('id', $this->getParagraphsUsedOnCurrentRevisions(), 'IN')
      ->condition('parent_type', 'paragraph', '<>')
      ->execute();

    if (!$select instanceof StatementInterface) {
      return;
    }
    $results = $select->fetchAll();

    $query = $this->database->insert(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME)
      ->fields([
        'hierarchy_id',
        'id',
        'parent_id',
        'type',
        'parent_type',
        'parent_bundle',
        'langcode',
        'level',
      ]);

    foreach ($results as $result) {
      $result = (array) $result;
      $result['hierarchy_id'] = $result['parent_type'] . "-" . $result['type'];
      $result['parent_bundle'] = $result['parent_type'];
      $result['level'] = 1;
      $query->values($result);
      $this->paragraphsHierarchyProcessLevel($result, $query);
    }

    $query->execute();

    $state_service->set('xray_audit.paragraphs_hierarchy_temporary_table_creation', time());
  }

  /**
   * Get the list of paragraphs that are being used on current revisions.
   *
   * This is used to not show unused paragraphs in paragraphs hierarchy.
   *
   * @return array
   *   IDs of unused paragraphs.
   */
  protected function getParagraphsUsedOnCurrentRevisions() {
    $paragraph_field_tables_query = $this->database->select('paragraphs_item_field_data', 'pd');
    $paragraph_field_tables_query->addExpression('CONCAT(pd.parent_type, :underscores , pd.parent_field_name)', 'table_name', [':underscores' => '__']);
    $paragraph_field_tables_query->groupBy('pd.parent_type');
    $paragraph_field_tables_query->groupBy('pd.parent_field_name');
    $executed_paragraph_field_tables_query = $paragraph_field_tables_query->execute();
    if (!$executed_paragraph_field_tables_query instanceof StatementInterface) {
      return [];
    }
    $paragraph_field_tables = $executed_paragraph_field_tables_query->fetchAllKeyed(0, 0);
    $paragraphs_ids = [];
    foreach ($paragraph_field_tables as $paragraph_field_table) {
      if ($paragraph_field_table === NULL) {
        continue;
      }
      [, $paragraph_field_table_field_name] = explode('__', $paragraph_field_table);
      if ($this->database->schema()->tableExists($paragraph_field_table)) {
        $executed_paragraphs_ids = $this->database->select($paragraph_field_table)
          ->fields($paragraph_field_table, [$paragraph_field_table_field_name . '_target_id'])
          ->execute();
        if ($executed_paragraphs_ids instanceof StatementInterface) {
          $paragraphs_ids = array_merge($paragraphs_ids, $executed_paragraphs_ids->fetchAllKeyed(0, 0));
        }
      }
    }
    return $paragraphs_ids;
  }

  /**
   * Process a level of the paragraphs Hierarchy temporally table.
   *
   * @param array $parent
   *   Parent result.
   * @param \Drupal\Core\Database\Query\Insert $query
   *   Drupal Query object.
   */
  protected function paragraphsHierarchyProcessLevel(array $parent, Insert $query): void {
    $results = [];
    $executed_results = $this->database->select('paragraphs_item_field_data')
      ->fields('paragraphs_item_field_data', [
        'id',
        'parent_id',
        'type',
        'langcode',
        'parent_type',
      ])
      ->condition('parent_id', $parent['id'])
      ->condition('parent_type', 'paragraph')
      ->execute();
    if ($executed_results) {
      $results = $executed_results->fetchAll();
    }
    foreach ($results as $result) {
      $result = (array) $result;
      $result['hierarchy_id'] = $parent['hierarchy_id'] . "-" . $result['type'];
      $result['parent_bundle'] = $parent['type'];
      $result['level'] = $parent['level'] + 1;
      $query->values($result);

      if ($result['level'] <= 10) {
        $this->paragraphsHierarchyProcessLevel($result, $query);
      }
    }
  }

  /**
   * Get the table configuration for the paragraphs Hierarchy temporally table.
   *
   * @return array
   *   Table configuration array.
   */
  protected function paragraphsHierarchyGetTableConfiguration(): array {
    return [
      'description' => 'Temporary table to get paragraph usage with hierarchy',
      'fields' => [
        'xra_id' => [
          'type' => 'serial',
          'not null' => TRUE,
        ],
        'hierarchy_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'id' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'parent_id' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'parent_type' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'parent_bundle' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'langcode' => [
          'type' => 'varchar',
          'length' => 10,
        ],
        'level' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => '1',
        ],
      ],
      'primary key' => ['xra_id'],
    ];
  }

  /**
   * Get the parameters from the URL query.
   *
   * @return array
   *   Parameters.
   */
  protected function getQueryParametersParagraphUsagePlace(): ?array {
    $parent_entity_type_key = 'parent';
    $paragraph_bundle_key = 'bundle';

    $parameters_from_url_query = $this->getParametersFromUrl();

    if (!isset($parameters_from_url_query[$parent_entity_type_key]) || !isset($parameters_from_url_query[$paragraph_bundle_key])) {
      return NULL;
    }
    return [
      $parent_entity_type_key => $parameters_from_url_query[$parent_entity_type_key],
      $paragraph_bundle_key => $parameters_from_url_query[$paragraph_bundle_key],
    ];
  }

  /**
   * Calculate the paragraph usage.
   *
   * @return array
   *   Paragraph usage places.
   */
  protected function paragraphsUsePlace(): array {
    $parameters = $this->getQueryParametersParagraphUsagePlace();
    if ($parameters === NULL) {
      return [];
    }

    $parent_entity_type = $parameters['parent'];
    $paragraph_bundle = $parameters['bundle'];

    $this->serviceEntityUseParagraph->initParameters($parent_entity_type, $paragraph_bundle);

    /** @var mixed[] $resultTable */
    $resultTable = $this->serviceEntityUseParagraph->getEntityUsePlaces();

    $count = 1;
    foreach ($resultTable as &$row) {
      $link = "<a href='@url' target='_blank'>Open page</a>";
      $row = [
        'num' => $count,
        'entity_type_parent' => $row['entity_type_parent'],
        'entity_type' => $row['entity_type'],
        'bundle' => $row['bundle'],
        'nid' => $row['nid'],
        'link' => new FormattableMarkup($link, ['@url' => $row['url']]),
        'status' => $row['status'],
      ];
      $count++;
    }

    $headerTable = [
      $this->t('Num.'),
      $this->t('Parent entity type'),
      $this->t('Entity type'),
      $this->t('Bundle'),
      $this->t('ID parent'),
      $this->t('Link'),
      $this->t('Status'),
    ];

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

  /**
   * Get values from current url query.
   */
  protected function getParametersFromUrl(): array {
    /**@var \Symfony\Component\HttpFoundation\Request $request*/
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      return [];
    }
    /**@var \Symfony\Component\HttpFoundation\ParameterBag $query*/
    $query = $request->query;
    $parameters = [];
    foreach ($query->all() as $key => $value) {
      $parameters[$key] = $value;
    }
    return $parameters;
  }

  /**
   * Create the requirements for the task.
   */
  public function paragraphsInstallActions(): void {
    if (!$this->database->schema()->tableExists(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME)) {
      $this->database->schema()->createTable(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME, $this->paragraphsHierarchyGetTableConfiguration());
    }
  }

  /**
   * Uninstall the requirements for the task.
   */
  public function paragraphsUninstallActions(): void {
    $this->state->set('xray_audit.paragraphs_hierarchy_temporary_table_creation', 0);
    $this->database->schema()->dropTable(self::XRA_PARAGRAPH_TEMPORARY_TABLE_NAME);
  }

}
