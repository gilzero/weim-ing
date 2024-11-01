<?php

namespace Drupal\xray_audit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * XrayAuditTaskPlugin plugin manager.
 */
class XrayAuditTaskPluginManager extends DefaultPluginManager {

  /**
   * Constructs Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/xray_audit/tasks',
      $namespaces,
      $module_handler,
      'Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface',
      'Drupal\xray_audit\Annotation\XrayAuditTaskPlugin'
    );
    $this->alterInfo('xray_audit_task_info');
    $this->setCacheBackend($cache_backend, 'xray_audit_task_plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      foreach ($definitions as &$definition) {
        $this->checkDependenciesPlugin($definition);
        $this->checkDependenciesInOperations($definition);
        $this->addRoutingInformation($definition);
      }
      $this->setCachedDefinitions($definitions);
    }

    return $definitions;
  }

  /**
   * Alter the definition of the plugin.
   *
   * Check the plugin dependencies. If all dependencies are no active,
   * definition is removed.
   *
   * @param array $definition
   *   Definition of on task plugin.
   */
  protected function checkDependenciesPlugin(array &$definition) {
    if (empty($definition['dependencies'])) {
      return;
    }
    if ($this->checkModuleDependencies($definition['dependencies']) === FALSE) {
      $definition = [];
    }
  }

  /**
   * Alter the definition of the plugin operations.
   *
   * Check the operation dependencies. If all dependencies are no active,
   * it is removed from definition.
   *
   * @param array $definition
   *   Definition of on task plugin.
   */
  protected function checkDependenciesInOperations(array &$definition) {
    if (empty($definition['operations'])) {
      return;
    }
    foreach ($definition['operations'] as $key_operation => $operation) {
      if (!empty($operation['dependencies'])) {
        if ($this->checkModuleDependencies($operation['dependencies']) === FALSE) {
          unset($definition['operations'][$key_operation]);
        }
      }
    }
  }

  /**
   * Check if the modules are active.
   *
   * @param array $modules
   *   List of modules.
   *
   * @return bool
   *   Result, true if all modules are active or false.
   */
  protected function checkModuleDependencies(array $modules): bool {
    foreach ($modules as $module) {
      if ($this->moduleHandler->moduleExists($module) !== TRUE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Add routing information to the definition.
   *
   * @param array $definition
   *   Definition of the plugin.
   */
  protected function addRoutingInformation(array &$definition) {
    if (empty($definition['operations'])) {
      return;
    }

    $operations_id = array_keys($definition['operations']);

    foreach ($operations_id as $operation_id) {
      $definition['operations'][$operation_id]['url'] = '/admin/reports/xray-audit/' . str_replace('_', '-', (string) $operation_id);
      $definition['operations'][$operation_id]['route_name'] = 'xray_audit.task_page.' . $operation_id;

    }
  }

  /**
   * Get the plugin definition from the operation.
   *
   * @param string $operation
   *   Operation.
   *
   * @return mixed[]
   *   Definition of the plugin.
   */
  public function getTaskPluginDefinitionFromOperation(string $operation) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      if (isset($definition['operations'][$operation])) {
        return $definition;
      }
    }
    return [];
  }

}
