<?php

namespace Drupal\danse_config\Plugin\Danse;

use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_config\Payload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "config",
 *   label = @Translation("Config"),
 *   description = @Translation("Manages all config entities.")
 * )
 */
class Config extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * Processes the event.
   *
   * @param string $id
   *   The ID.
   * @param array $data
   *   The data.
   */
  public function processEvent(string $id, array $data): void {
    $this->createEvent('save', $id, new Payload($id, $data));
  }

}
