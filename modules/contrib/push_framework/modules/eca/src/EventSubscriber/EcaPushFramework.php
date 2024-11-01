<?php

namespace Drupal\eca_push_framework\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\EventSubscriber\EcaExecutionSubscriberBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\push_framework\Event\ChannelEventBase;
use Drupal\push_framework\Event\ChannelPostRender;
use Drupal\push_framework\Event\ChannelPrepareTemplates;
use Drupal\push_framework\Event\ChannelPreRender;

/**
 * ECA event subscriber regarding form events.
 */
class EcaPushFramework extends EcaExecutionSubscriberBase {

  /**
   * Subscriber method after initial execution.
   *
   * Removes the form data provider from the Token service.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $after_event
   *   The according event.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $after_event): void {
    $event = $after_event->getEvent();
    if ($event instanceof ChannelEventBase) {
      $dto = $this->tokenService->getTokenData('push_notification');
      if ($dto instanceof DataTransferObject) {
        if ($event instanceof ChannelPrepareTemplates) {
          $event
            ->setDisplayMode($dto->get('display_mode')->getString())
            ->setSubject($dto->get('subject')->getString())
            ->setBody($dto->get('body')->getString())
            ->setTextFormat($dto->get('text_format')->getString())
            ->setIsHtml((bool) $dto->get('is_html')->getValue());
        }
        if ($event instanceof ChannelPreRender) {
          $event->setElements((array) $dto->get('elements'));
        }
        if ($event instanceof ChannelPostRender) {
          $event->setOutput($dto->get('output')->getString());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = [
      'onAfterInitialExecution',
      100,
    ];
    return $events;
  }

}
