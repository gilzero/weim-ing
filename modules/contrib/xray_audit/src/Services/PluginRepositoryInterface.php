<?php

namespace Drupal\xray_audit\Services;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginInterface;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;

/**
 * Interface for Plugin Repository.
 */
interface PluginRepositoryInterface {

  /**
   * Parameter name for group plugin.
   */
  const PARAMETER_GROUP_PLUGIN = 'group_plugin';

  /**
   * Parameter name for task plugin.
   */
  const PARAMETER_TASK_PLUGIN = 'task_plugin';

  /**
   * Parameter name for batch process.
   */
  const PARAMETER_BATCH_OPERATION = 'batch_process';

  /**
   * Parameter name for task plugin operation.
   */
  const PARAMETER_TASK_OPERATION = 'task_operation';

  /**
   * Parameter name for operation.
   */
  const PARAMETER_OPERATION = 'operation';

  /**
   * Get Url for task pages.
   *
   * @param string $operation
   *   Operation.
   * @param array $options_query
   *   Options query.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function getTaskPageOperationFromIdOperation(string $operation, array $options_query = NULL);

  /**
   * Get url for group page.
   *
   * @param string $group_plugin_id
   *   Group plugin id.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function getGroupPageUrl(string $group_plugin_id);

  /**
   * Get Url for batch process.
   *
   * @param string $task_plugin_id
   *   Task plugin.
   * @param string $batch_process
   *   Batch process.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function getBatchUrl(string $task_plugin_id, string $batch_process);

  /**
   * Get the element from compound string.
   *
   * @param string $task_plugin_operation
   *   Task plugin id + operation.
   *
   * @return array|null
   *   Task plugin id and operation.
   */
  public function explodeFromParameterTaskOperation(string $task_plugin_operation): ?array;

  /**
   * Get the task plugin from id.
   *
   * @param string $task_plugin_id
   *   Task plugin id.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface|null
   *   Plugin object or null.
   */
  public function getInstancePluginTask(string $task_plugin_id): ?XrayAuditTaskPluginInterface;

  /**
   * Get the group plugin from id.
   *
   * @param string $group_plugin_id
   *   Group plugin id.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditGroupPluginInterface|null
   *   Plugin object or null.
   */
  public function getInstancePluginGroup(string $group_plugin_id): ?XrayAuditGroupPluginInterface;

  /**
   * Get all group plugin definitions.
   *
   * @return array
   *   Plugins definitions.
   */
  public function getGroupPluginDefinitions(): array;

  /**
   * Get all task plugin definitions.
   *
   * @return array
   *   Plugins definitions.
   */
  public function getTaskPluginDefinitions(): array;

  /**
   * Get an instance of a plugin using operation id.
   *
   * @param string $operation
   *   Operation.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface|null
   *   Plugin instance.
   */
  public function getInstancePluginTaskFromOperation(string $operation): ?XrayAuditTaskPluginInterface;

  /**
   * Set cache with invalidation by tags.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $value
   *   The data to store in the cache.
   * @param array $tags
   *   The tags to invalidate.
   */
  public function setCacheTagsInv(string $cid, $value, $tags = []);

  /**
   * Set cache with temporal invalidation.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $value
   *   The data to store in the cache.
   * @param int $duration
   *   The cache object duration.
   */
  public function setCacheTempInv(string $cid, $value, int $duration);

  /**
   * Get cached data.
   *
   * @param string $cid
   *   The cache ID to retrieve.
   *
   * @return mixed
   *   The cached data or FALSE on failure.
   */
  public function getCachedData(string $cid);

  /**
   * Clear all cached objects.
   */
  public function clearAllCache();

  /**
   * Delete all cached objects.
   *
   * @param string $cid
   *   The cache ID to delete.
   */
  public function deleteCache(string $cid);

  /**
   * Invalidate cache.
   *
   * @param string $cid
   *   The cache ID to invalidate.
   */
  public function invalidateCache(string $cid);

  /**
   * Remove the bin.
   */
  public function removeCacheBin();

}
