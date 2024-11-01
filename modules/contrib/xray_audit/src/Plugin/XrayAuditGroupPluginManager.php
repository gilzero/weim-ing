<?php

namespace Drupal\xray_audit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * XrayAuditGroupPluginManager plugin manager.
 */
class XrayAuditGroupPluginManager extends DefaultPluginManager {

  /**
   * Constructs Drupal\xray_audit\Plugin\XrayAuditGroupPluginManager object.
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
    'Plugin/xray_audit/groups',
    $namespaces,
    $module_handler,
      'Drupal\xray_audit\Plugin\XrayAuditGroupPluginInterface',
      'Drupal\xray_audit\Annotation\XrayAuditGroupPlugin'
    );
    $this->alterInfo('xray_audit_group_info');
    $this->setCacheBackend($cache_backend, 'xray_audit_group_plugin');
  }

}
