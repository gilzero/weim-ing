<?php

namespace Drupal\danse_webhook\Plugin\Danse;

use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_webhook\Payload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "webhook",
 *   label = @Translation("Webhook"),
 *   description = @Translation("Provides a webhook to create events.")
 * )
 */
class Webhook extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * Creates a new webhook event.
   *
   * @param string $agent
   *   The agent.
   * @param string $label
   *   The label.
   * @param \Drupal\danse_webhook\Payload $payload
   *   The payload.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The webhook event if it was possible to create it, NULL otherwise.
   */
  public function createWebhookEvent(string $agent, string $label, Payload $payload): ?EventInterface {
    return $this->createEvent($agent, $label, $payload);
  }

}
