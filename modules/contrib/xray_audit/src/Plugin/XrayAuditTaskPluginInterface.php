<?php

namespace Drupal\xray_audit\Plugin;

/**
 * Interface for xray_audit_query_data plugins.
 */
interface XrayAuditTaskPluginInterface {

  /**
   * Returns the group that this task belongs to.
   *
   * @return string
   *   Group.
   */
  public function getGroup();

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Returns the translated description.
   *
   * @return string
   *   The translated description.
   */
  public function getDescription();

  /**
   * The position of the task in the group task.
   *
   * @return int
   *   The position.
   */
  public function getSort();

  /**
   * The operations defined in the plugin.
   *
   * @return array
   *   The operations.
   */
  public function getOperations();

  /**
   * Get class for batch process.
   *
   * @param string $batch_id
   *   Batch id.
   *
   * @return string|null
   *   The batch process class.
   */
  public function getBatchClass(string $batch_id);

  /**
   * Get the result data from operation.
   *
   * @param string $operation
   *   Operation to do.
   *
   * @return array
   *   Data result operation.
   */
  public function getDataOperationResult(string $operation = '');

  /**
   * Check if is local task case.
   *
   * @return bool
   *   Is local task case.
   */
  public function isLocalTaskCase(): bool;

  /**
   * Return a render array.
   *
   * @param array $data
   *   Data to build the render array.
   * @param string $operation
   *   Operation.
   *
   * @return array
   *   Render array.
   */
  public function buildDataRenderArray(array $data, string $operation = '');

}
