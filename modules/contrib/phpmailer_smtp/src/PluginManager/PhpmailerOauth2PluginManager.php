<?php

namespace Drupal\phpmailer_smtp\PluginManager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The PHPMailer OAuth 2 plugin manager.
 */
class PhpmailerOauth2PluginManager extends DefaultPluginManager implements PhpmailerOauth2PluginManagerInterface {

  /**
   * Constructs a PhpmailerOauth2PluginManager object.
   *
   * @param \Traversable $namespaces
   *   Namespaces to be searched for the plugin.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct('Plugin/PhpmailerOauth2', $namespaces, $moduleHandler, 'Drupal\phpmailer_smtp\Plugin\PhpmailerOauth2\PhpmailerOauth2PluginInterface', 'Drupal\phpmailer_smtp\Annotation\PhpmailerOauth2');

    $this->alterInfo('phpmailer_oauth2_info');
    $this->setCacheBackend($cacheBackend, 'phpmailer_oauth2');
  }

}
