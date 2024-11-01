<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for xray_audit_query_task_data plugins.
 */
abstract class XrayAuditQueryTaskPluginBase extends XrayAuditTaskPluginBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Service "xray_audit.plugin_repository".
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

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
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   *
   * @phpstan-consistent-constructor
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LanguageManagerInterface $language_manager,
    PluginRepositoryInterface $pluginRepository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->pluginRepository = $pluginRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('xray_audit.plugin_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataRenderArray(array $data, string $operation = '') {

    $description = '';
    $operation_data = $this->getOperations()[$operation] ?? NULL;
    if ($operation_data) {
      $description = $operation_data['description'] ?? '';
    }

    $header = $data['header_table'] ?? [];
    $rows = $data['results_table'] ?? [];

    $build = [];

    if ($description) {
      $build['#markup'] = '<p>' . $description . '</p>';
    }

    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
