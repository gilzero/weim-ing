<?php

declare(strict_types=1);

namespace Drupal\raven\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * Sanitizes sensitive data, such as passwords, before sending to Sentry.
 */
class SanitizeIntegration implements IntegrationInterface {

  /**
   * This constant defines the mask string used to strip sensitive information.
   */
  const STRING_MASK = '********';

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
    $request = $event->getRequest();
    if (isset($request['data']) && is_array($request['data']) && !empty($request['data']['pass'])) {
      $request['data']['pass'] = self::STRING_MASK;
    }
    $event->setRequest($request);
  }

}
