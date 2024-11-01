<?php

namespace Drupal\danse_config;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\danse\PluginManager;
use Drupal\danse_config\Plugin\Danse\Config;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The config plugin.
   *
   * @var \Drupal\danse_config\Plugin\Danse\Config
   */
  protected Config $plugin;

  /**
   * Indicator if the plugin is active.
   *
   * @var bool
   */
  protected bool $active;

  /**
   * Constructs a ConfigSubscriber.
   *
   * @param \Drupal\danse\PluginManager $plugin_manager
   *   The DANSE plugin manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(PluginManager $plugin_manager) {
    $plugin = $plugin_manager->createInstance('config');
    if ($plugin instanceof Config) {
      $this->plugin = $plugin;
    }
    $this->active = TRUE;
  }

  /**
   * Determine if this subscriber is enabled.
   *
   * @return bool
   *   TRUE, if this subscriber is enabled, FALSE otherwise.
   */
  protected function enabled(): bool {
    // @todo Make this configurable.
    // ATTENTION: while installing the module, keep this disabled!!!
    return TRUE;
  }

  /**
   * Saves changed config to a configurable directory.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Public function onConfigSave event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    if ($this->active && $this->enabled()) {
      $object = $event->getConfig();
      $this->plugin->processEvent($object->getName(), $object->get());
    }
  }

  /**
   * Saves changed config translation to a configurable directory.
   *
   * @param \Drupal\language\Config\LanguageConfigOverrideCrudEvent $event
   *   Public function onConfigTranslationSave event.
   */
  public function onConfigTranslationSave(LanguageConfigOverrideCrudEvent $event): void {
    if ($this->active && $this->enabled()) {
      $object = $event->getLanguageConfigOverride();
      $this->plugin->processEvent($object->getName(), $object->get());
    }
  }

  /**
   * Turn off this subscriber on importing configuration.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   Public function onConfigImportValidate event.
   */
  public function onConfigImportValidate(ConfigImporterEvent $event): void {
    $this->active = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImportValidate', 1024];
    if (class_exists(LanguageConfigOverrideEvents::class)) {
      $events[LanguageConfigOverrideEvents::SAVE_OVERRIDE][] = [
        'onConfigTranslationSave',
        0,
      ];
    }
    return $events;
  }

}
