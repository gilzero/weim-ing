<?php

declare(strict_types=1);

namespace Drupal\raven\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * Removes function calling arguments from exception stack frames.
 */
class RemoveExceptionFrameVarsIntegration implements IntegrationInterface {

  /**
   * {@inheritdoc}
   */
  public function setupOnce(): void {
    Scope::addGlobalEventProcessor(function (Event $event): Event {
      $currentHub = SentrySdk::getCurrentHub();
      $integration = $currentHub->getIntegration(self::class);
      $client = $currentHub->getClient();

      // The client bound to the current hub, if any, could not have this
      // integration enabled. If this is the case, bail out.
      if (NULL === $integration || NULL === $client) {
        return $event;
      }

      $this->processEvent($event, $client->getOptions());

      return $event;
    });
  }

  /**
   * {@inheritdoc}
   */
  private function processEvent(Event $event, Options $options): void {

    // Remove function calling arguments from exception stack frames.
    foreach ($event->getExceptions() as $exception) {
      if ($stacktrace = $exception->getStacktrace()) {
        foreach ($stacktrace->getFrames() as $frame) {
          $frame->setVars([]);
        }
      }
    }

    // Also remove function calling arguments from event stacktrace added by
    // \Sentry\Client::addMissingStacktraceToEvent().
    if ($stacktrace = $event->getStacktrace()) {
      foreach ($stacktrace->getFrames() as $frame) {
        $frame->setVars([]);
      }
    }
  }

}
