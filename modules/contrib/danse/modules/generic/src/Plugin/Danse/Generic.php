<?php

namespace Drupal\danse_generic\Plugin\Danse;

use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_generic\Payload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "generic",
 *   label = @Translation("Generic"),
 *   description = @Translation("Provides generic DANSE events.")
 * )
 */
class Generic extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * Creates a new generic event.
   *
   * @param string $topicId
   *   The identifier for the topic this event belongs to.
   * @param string $label
   *   The label.
   * @param \Drupal\danse_generic\Payload $payload
   *   The payload.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The generic event if it was possible to create it, NULL otherwise.
   */
  public function createGenericEvent(string $topicId, string $label, Payload $payload): ?EventInterface {
    return $this->createEvent($topicId, $label, $payload);
  }

}
