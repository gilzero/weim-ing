<?php
/**
 * @file
 *
 * Helped by https://www.drupal.org/docs/creating-modules/subscribe-to-and-dispatch-events#s-my-first-drupal-8-event-and-event-dispatch
 */
namespace Drupal\site_audit_send\Event;

use Drupal\site_audit_report_entity\Entity\SiteAuditReport;
use Drupal\Component\EventDispatcher\Event;
use GuzzleHttp\Psr7\Response;

/**
 * Event that is fired when a report is sent.
 */
class SiteAuditSentEvent extends Event {

  const EVENT_NAME = 'site_audit_report_sent';

  public $report;
  public $response;

  /**
   * Constructs the object.
   *
   * @param SiteAuditReport $report
   *   The report that was just sent.
   *
   * @param Response $response
   *
   */
  public function __construct(SiteAuditReport $report, Response $response) {
    $this->report = $report;
    $this->response = $response;
  }

}
