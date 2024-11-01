<?php

namespace Drupal\push_framework;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\push_framework\Annotation\ChannelPlugin;

/**
 * PushFramework plugin manager.
 */
class ChannelPluginManager extends DefaultPluginManager {

  /**
   * The Push Framework configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Constructs PluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the Push Framework settings.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('push_framework.settings');
    parent::__construct(
      'Plugin/PushFrameworkChannel',
      $namespaces,
      $module_handler,
      ChannelPluginInterface::class,
      ChannelPlugin::class
    );
    $this->alterInfo('push_framework_channel_info');
    $this->setCacheBackend($cache_backend, 'push_framework_channel_plugins', ['push_framework_channel_plugins']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(): ?array {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $id => $definition) {
      $orderNumber = !$this->config->get('order_' . $id) ? 1 : $this->config->get('order_' . $id);
      $definitions[$id]['weight'] = $orderNumber;
    }
    uasort($definitions, static function ($d1, $d2) {
      if ($d1['weight'] < $d2['weight']) {
        return -1;
      }
      if ($d1['weight'] > $d2['weight']) {
        return 1;
      }
      return 0;
    });
    return $definitions;
  }

}
