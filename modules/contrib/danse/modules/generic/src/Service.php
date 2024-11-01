<?php

namespace Drupal\danse_generic;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PluginManager;
use Drupal\danse_generic\Plugin\Danse\Generic;

/**
 * Provides services for the DANSE generic sub-module.
 */
class Service {

  /**
   * The plugin.
   *
   * @var \Drupal\danse_generic\Plugin\Danse\Generic
   */
  protected Generic $plugin;

  /**
   * Constructor for the generic DANSE event.
   *
   * @param \Drupal\danse\PluginManager $pluginManager
   *   The DANSE plugin manager.
   */
  public function __construct(PluginManager $pluginManager) {
    try {
      $plugin = $pluginManager->createInstance('generic');
      if ($plugin instanceof Generic) {
        $this->plugin = $plugin;
      }
    }
    catch (PluginException $e) {
      // Can be ignored.
    }
  }

  /**
   * Create a new generic DANSE event.
   *
   * @param string $topicId
   *   The topic ID.
   * @param string $label
   *   The label.
   * @param mixed $data
   *   The arbitrary data.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The event interface.
   */
  public function createDanseEvent(string $topicId, string $label, mixed $data): ?EventInterface {
    return $this->plugin->createGenericEvent($topicId, $label, new Payload($label, $data));
  }

}
