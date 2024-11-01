<?php

namespace Drupal\danse_log\EventSubscriber;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\danse_log\Event\LogEvent;
use Drupal\danse_log\LogEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Log event subscriber.
 */
class DefaultLog implements EventSubscriberInterface {

  /**
   * New log item event handler.
   *
   * @param \Drupal\danse_log\Event\LogEvent $event
   *   The log event.
   */
  public function onNewLog(LogEvent $event): void {
    if ($event->getPayload()->getLevel() <= RfcLogLevel::ERROR) {
      $event->setRelevant();
    }
    if ($event->isRelevant() && $event->getPayload()->getMessage() === 'CAPTCHA validation error: unknown CAPTCHA session ID (%csid).') {
      $event->setIrrelevant();
    }
    if ($event->isRelevant() && $event->getPayload()->getMessage() === 'Illegal choice %choice in %name element.') {
      $event->setIrrelevant();
    }
    if ($event->isRelevant() && str_starts_with($event->getPayload()
      ->getMessage(), 'Import of string ')) {
      $event->setIrrelevant();
    }
    if ($event->isRelevant() && ($context = $event->getPayload()
      ->getContext()) && isset($context['@message']) && str_starts_with($context['@message'], 'Not acceptable format: ')) {
      $event->setIrrelevant();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LogEvents::LOG => ['onNewLog'],
    ];
  }

}
