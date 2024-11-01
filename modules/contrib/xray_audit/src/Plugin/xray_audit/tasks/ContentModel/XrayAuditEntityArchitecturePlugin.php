<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentModel;

use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\CsvDownloadManagerInterface;
use Drupal\xray_audit\Services\EntityArchitectureInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin entity field architecture.
 *
 * @XrayAuditTaskPlugin (
 *   id = "entity_architecture",
 *   label = @Translation("Entity architecture"),
 *   description = @Translation("Entity architecture."),
 *   group = "content_model",
 *   sort = 5,
 *   operations = {
 *      "content_entity_definition" = {
 *          "label" = "Content entity definitions",
 *          "description" = "Definitions of all content entities (nodes, paragraphs, blocks, etc.)."
 *       }
 *   },
 *   dependencies = {"field"}
 * )
 */
class XrayAuditEntityArchitecturePlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "xray_audit.entity_field_architecture".
   *
   * @var \Drupal\xray_audit\Services\EntityArchitectureInterface
   */
  protected $entityArchitectureService;

  /**
   * Service "xray_audit.plugin_repository".
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

  /**
   * Service "xray_audit.csv_download_manager".
   *
   * @var \Drupal\xray_audit\Services\CsvDownloadManagerInterface
   */
  protected $csvDownloadManager;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\xray_audit\Services\EntityArchitectureInterface $entity_architecture_service
   *   Service "xray_audit.entity_field_architecture".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   * @param \Drupal\xray_audit\Services\CsvDownloadManagerInterface $csvDownloadManager
   *   Service "xray_audit.csv_download_manager".
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityArchitectureInterface $entity_architecture_service, PluginRepositoryInterface $pluginRepository, CsvDownloadManagerInterface $csvDownloadManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityArchitectureService = $entity_architecture_service;
    $this->pluginRepository = $pluginRepository;
    $this->csvDownloadManager = $csvDownloadManager;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-consistent-constructor
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('xray_audit.entity_architecture'),
      $container->get('xray_audit.plugin_repository'),
      $container->get('xray_audit.csv_download_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'content_entity_definition':
        return $this->entityArchitectureService->getDataForEntityFieldArchitecture();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $build = [];

    $headers = [
      $this->t('Entity'),
      $this->t('Bundle'),
      $this->t('Type'),
      $this->t('Machine name'),
      $this->t('Label'),
      $this->t('Description'),
      $this->t('Data Type'),
      $this->t('Computed'),
      $this->t('Data Type Settings'),
      $this->t('Cardinality'),
      $this->t('Mandatory'),
      $this->t('Read Only'),
      $this->t('Translatable'),
      $this->t('Revisionable'),
      $this->t('Default value'),
      $this->t('Default value callback'),
      $this->t('Form widget'),
    ];
    $rows = [];

    foreach ($data as $data_row) {
      $rows[] = [
        "data" => [
          $data_row['content']['entity'] ?? '',
          $data_row['content']['bundle'] ?? '',
          $data_row['content']['type'] ?? '',
          $data_row['content']['machine_name'] ?? '',
          $data_row['content']['label'] ?? '',
          $data_row['content']['description'] ?? '',
          $data_row['content']['data_type'] ?? '',
          $data_row['content']['computed'] ?? '',
          $data_row['content']['data_type_settings'] ?? '',
          $data_row['content']['cardinality'] ?? '',
          $data_row['content']['mandatory'] ?? '',
          $data_row['content']['read_only'] ?? '',
          $data_row['content']['translatable'] ?? '',
          $data_row['content']['revisionable'] ?? '',
          $data_row['content']['default_value'] ?? '',
          $data_row['content']['default_value_callback'] ?? '',
          $data_row['content']['form_widget'] ?? '',
        ],
        "class" => ($data_row['content']['type'] === 'entity') ? ['xray-audit--highlighted'] : [],
      ];

    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#weight' => 10,
      '#attributes' => [
        'class' => ['xray-audit__table'],
      ],
      '#attached' => [
        'library' => [
          'xray_audit/xray_audit',
        ],
      ],
    ];

    $build['download'] = [
      '#type' => 'link',
      '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation(
        'content_entity_definition',
        ['download']
      ),
      '#title' => $this->t('Download'),
      '#weight' => 5,
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
        ],
      ],
    ];

    if ($this->csvDownloadManager->downloadCsv()) {
      $rows_csv = [];
      foreach ($rows as $key => $row) {
        $rows_csv[$key] = $row['data'];
      }
      $this->csvDownloadManager->createCsv($rows_csv, $headers, $operation ?? '');
    }

    return $build;

  }

}
