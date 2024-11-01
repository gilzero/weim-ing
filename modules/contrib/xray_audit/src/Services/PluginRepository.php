<?php

namespace Drupal\xray_audit\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\xray_audit\Plugin\XrayAuditGroupPluginInterface;
use Drupal\xray_audit\Plugin\XrayAuditGroupPluginManager;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager;

/**
 * Retrieve data from entity about display modes.
 */
class PluginRepository implements PluginRepositoryInterface {

  /**
   * Group plugin manager.
   *
   * @var \Drupal\xray_audit\Plugin\XrayAuditGroupPluginManager
   */
  protected $groupPluginManager;

  /**
   * Task  plugin manager.
   *
   * @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager
   */
  protected $taskPluginManager;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Cache manager.
   *
   * @var \Drupal\xray_audit\Services\CacheManagerInterface
   */
  public $cacheManager;

  /**
   * Constructs the service.
   *
   * @param \Drupal\xray_audit\Plugin\XrayAuditGroupPluginManager $plugin_manager_xray_audit_group
   *   Group plugin manager.
   * @param \Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager $plugin_manager_xray_audit_task
   *   Task  plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\xray_audit\Services\CacheManagerInterface $xray_cache_manager
   *   Cache manager.
   */
  public function __construct(XrayAuditGroupPluginManager $plugin_manager_xray_audit_group, XrayAuditTaskPluginManager $plugin_manager_xray_audit_task, LoggerChannelFactoryInterface $logger_factory, CacheManagerInterface $xray_cache_manager) {
    $this->groupPluginManager = $plugin_manager_xray_audit_group;
    $this->taskPluginManager = $plugin_manager_xray_audit_task;
    $this->logger = $logger_factory->get('xray_audit');
    $this->cacheManager = $xray_cache_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPageUrl(string $group_plugin_id) {
    return Url::fromRoute(
      'xray_audit.group_page',
      [self::PARAMETER_GROUP_PLUGIN => $group_plugin_id]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskPageOperationFromIdOperation(string $operation, array $options_query = NULL) {

    $query_parameters = ['query' => []];

    if (!empty($options_query)) {
      $query_parameters = ['query' => $options_query];
    }
    return Url::fromRoute('xray_audit.task_page.' . $operation, [], $query_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchUrl(string $task_plugin, string $batch_process) {
    return Url::fromRoute(
      'xray_audit.batch_process',
      [
        self::PARAMETER_TASK_PLUGIN => $task_plugin,
        self::PARAMETER_BATCH_OPERATION => $batch_process,
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function explodeFromParameterTaskOperation(string $task_plugin_operation): ?array {
    $task_plugin_operation_array = explode('-', $task_plugin_operation);
    if (count($task_plugin_operation_array) !== 2) {
      return NULL;
    }
    return [
      self::PARAMETER_TASK_PLUGIN => $task_plugin_operation_array[0],
      self::PARAMETER_OPERATION => $task_plugin_operation_array[1],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInstancePluginTask(string $task_plugin): ?XrayAuditTaskPluginInterface {
    try {
      /** @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface $task_plugin */
      $task_plugin = $this->taskPluginManager->createInstance($task_plugin);
      if ($task_plugin instanceof XrayAuditTaskPluginInterface) {
        return $task_plugin;
      }
    }
    catch (\Exception $error) {
      $this->logger->error($error->getMessage());
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public function getInstancePluginTaskFromOperation(string $operation): ?XrayAuditTaskPluginInterface {
    try {
      $plugin_definition = $this->taskPluginManager->getTaskPluginDefinitionFromOperation($operation);

      if (empty($plugin_definition)) {
        throw new \Exception('Plugin definition not found from operation.');
      }

      /** @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface $task_plugin */
      $task_plugin = $this->taskPluginManager->createInstance($plugin_definition['id']);
      if ($task_plugin instanceof XrayAuditTaskPluginInterface) {
        return $task_plugin;
      }
    }
    catch (\Exception $error) {
      $this->logger->error($error->getMessage());
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public function getInstancePluginGroup(string $group_task): ?XrayAuditGroupPluginInterface {
    try {
      /** @var \Drupal\xray_audit\Plugin\XrayAuditGroupPluginInterface $$group_task */
      $group_task = $this->groupPluginManager->createInstance($group_task);
      if ($group_task instanceof XrayAuditGroupPluginInterface) {
        return $group_task;
      }
    }
    catch (\Exception $error) {
      $this->logger->error($error->getMessage());
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPluginDefinitions(): array {
    return $this->groupPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskPluginDefinitions(): array {
    return $this->taskPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheTagsInv(string $cid, $value, $tags = []) {
    $this->cacheManager->setCacheTagsInv($cid, $value, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheTempInv(string $cid, $value, int $duration) {
    $this->cacheManager->setCacheTempInv($cid, $value, $duration);
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedData(string $cid) {
    return $this->cacheManager->getCachedData($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function clearAllCache() {
    $this->cacheManager->clearAllCache();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCache(string $cid) {
    $this->cacheManager->deleteCache($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateCache(string $cid) {
    $this->cacheManager->invalidateCache($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function removeCacheBin() {
    $this->cacheManager->removeBin();
  }

}
