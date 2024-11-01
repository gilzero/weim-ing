<?php

namespace Drupal\xray_audit_insight\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * XrayAuditInsightPlugin plugin manager.
 */
class XrayAuditInsightPluginManager extends DefaultPluginManager {

  /**
   * Constructs Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginManager object.
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
      'Plugin/insights',
      $namespaces,
      $module_handler,
      'Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginInterface',
      'Drupal\xray_audit_insight\Annotation\XrayAuditInsightPlugin'
    );
    $this->alterInfo('xray_audit_insight_info');
    $this->setCacheBackend($cache_backend, 'xray_audit_insight_plugin');
  }

}
