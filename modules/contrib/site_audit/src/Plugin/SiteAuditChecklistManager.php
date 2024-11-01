<?php

namespace Drupal\site_audit\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Site Audit Report plugin manager.
 */
class SiteAuditChecklistManager extends DefaultPluginManager {

  /**
   * Constructs a new SiteAuditChecklistManager object.
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
    parent::__construct('Plugin/SiteAuditChecklist', $namespaces, $module_handler, 'Drupal\site_audit\Plugin\SiteAuditChecklistInterface', 'Drupal\site_audit\Annotation\SiteAuditChecklist');

    $this->alterInfo('site_audit_site_audit_checklist_info');
    $this->setCacheBackend($cache_backend, 'site_audit_site_audit_checklist_plugins');
  }

  /**
   * @inherit
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    ksort($definitions);
    return $definitions;
  }

}
