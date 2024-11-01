<?php

namespace Drupal\site_audit_send\EventSubscriber;

use Drupal\automated_cron\EventSubscriber\AutomatedCron;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\site_audit_report_entity\Entity\SiteAuditReport;
use Drupal\site_audit_send\Event\SiteAuditSentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Site Audit Remote Client event subscriber.
 */
class SiteAuditSendSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SiteAuditSentEvent::EVENT_NAME => 'onReportSent'
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param SiteAuditSentEvent $event
   */
  public function onReportSent(SiteAuditSentEvent $event) {
    // \Drupal::messenger()->addStatus(t('Just an example.'));
  }
}
