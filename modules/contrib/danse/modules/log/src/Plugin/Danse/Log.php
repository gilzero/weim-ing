<?php

namespace Drupal\danse_log\Plugin\Danse;

use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_log\Payload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "log",
 *   label = @Translation("Log"),
 *   description = @Translation("Manages log events.")
 * )
 */
class Log extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * Create a new DANSE log event.
   *
   * @param string $topic
   *   The topic.
   * @param string $message
   *   The log message.
   * @param \Drupal\danse_log\Payload $payload
   *   The log payload.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The DANSE log event, if it was possible to create one, NULL otherwise.
   */
  public function createLogEvent($topic, $message, Payload $payload): ?EventInterface {
    return $this->createEvent(mb_substr($topic, 0, 32), $message, $payload, TRUE, TRUE, FALSE);
  }

}
