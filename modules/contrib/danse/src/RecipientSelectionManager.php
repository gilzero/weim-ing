<?php

namespace Drupal\danse;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\danse\Annotation\DanseRecipientSelection;

/**
 * DANSE recipient selection manager.
 */
class RecipientSelectionManager extends DefaultPluginManager {

  /**
   * Constructs RecipientSelectionManager object.
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
      'Plugin/DanseRecipientSelection',
      $namespaces,
      $module_handler,
      RecipientSelectionInterface::class,
      DanseRecipientSelection::class
    );
    $this->alterInfo('danse_recipient_selection_info');
    $this->setCacheBackend($cache_backend, 'danse_recipient_selection_plugins', ['danse_recipient_selection_plugins']);
  }

  /**
   * Returns a list of plugin usable in forms.
   *
   * @return array
   *   The list of plugins.
   */
  public function pluginListForSelect(): array {
    $plugins = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $plugins[$id] = $definition['label'];
    }
    return $plugins;
  }

}
