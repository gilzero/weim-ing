<?php

namespace Drupal\site_audit\Commands;

use Drupal\site_audit\Plugin\SiteAuditCheckManager;
use Drupal\site_audit\Plugin\SiteAuditChecklistManager;
use Drupal\site_audit\Renderer\Html;
use Drupal\site_audit\Renderer\Markdown;
use Drupal\site_audit\Renderer\Json;
use Drupal\site_audit\Renderer\Console;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Log\LoggerAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * SiteAudit Drush commandfile.
 */
class SiteAuditCommands extends DrushCommands implements IOAwareInterface, LoggerAwareInterface, ConfigAwareInterface, CustomEventAwareInterface {

  use CustomEventAwareTrait;
  use StringTranslationTrait;

  /**
   * The Site Audit Check manager.
   *
   * @var \Drupal\site_audit\Plugin\SiteAuditCheckManager
   */
  protected $auditCheckManager;

  /**
   * The Site Audit Report manager.
   *
   * @var \Drupal\site_audit\Plugin\SiteAuditChecklistManager
   */
  protected $auditChecklistManager;

  /**
   * Constructs the SiteAuditCommands.
   *
   * @param \Drupal\site_audit\Plugin\SiteAuditCheckManager $auditCheckManager
   *   The Site Audit Check manager.
   * @param \Drupal\site_audit\Plugin\SiteAuditChecklistManager $auditCheckManager
   *   The Site Audit Report manager.
   */
  public function __construct(SiteAuditCheckManager $auditCheckManager, SiteAuditChecklistManager $auditCheckListManager) {
    parent::__construct();
    $this->auditCheckManager = $auditCheckManager;
    $this->auditChecklistManager = $auditCheckListManager;
  }

  /**
   * Run a Site Audit report.
   *
   * @param $checklist
   *   The particular report to run. Omit this argument to choose from available reports.
   *
   * @option skip
   *   List of available reports.
   * @option format
   *   Format you which the report is to be in (html, text, json, markdown)
   * @option detail
   *   Show details when no issues found for the check.
   * @option bootstrap
   *   Wrap the report in HTML with Bootstrap derived styles. Forces --format=html
   * @usage site_audit:audit
   *   Run all Site Audit reports
   *
   * @command site_audit:audit
   * @aliases audit
   *
   * @usage audit watchdog
   *   only run the watchdog report
   * @usage audit --skip=block,status
   *  skip the block and status reports
   */
  public function audit($checklist, $options = ['skip' => 'none', 'format' => 'text', 'detail' => FALSE, 'bootstrap' => FALSE]) {
    if ($options['bootstrap']) {
      // Bootstrap implies html.
      $options['format'] = 'html';
    }
    $boot_manager = Drush::bootstrapManager();

    $output = $this->output();
    $out = '';
    $checklistDefinitions = $this->auditChecklistManager->getDefinitions();

    $checklists = [];
    if ($checklist == 'all') {
      // Run all reports unless it is explicitly skipped.
      $skipped = explode(',', $options['skip']);
      foreach ($checklistDefinitions as $checklist) {
        $isSkipped = array_search($checklist['id'], $skipped);
        if ($isSkipped === FALSE) {
          $checklists[] = $this->auditChecklistManager->createInstance($checklist['id'], $options);
        }
      }
    }
    elseif (!empty($checklist)) {
      $checklists[] = $this->auditChecklistManager->createInstance($checklist, $options);
    }

    switch ($options['format']) {
      case 'html':
        $renderer = new Html($checklists, $this->logger, $options, $output);
        $out .= $renderer->render(TRUE);
        break;

      case 'json';
        foreach ($checklists as $checklist) {
          $renderer = new Json($checklist, $this->logger, $options, $output);
          $out .= $renderer->render(TRUE);
        }
        break;

      case 'markdown':
        foreach ($checklists as $checklist) {
          $renderer = new Markdown($checklist, $this->logger, $options, $output);
          $out .= $renderer->render(TRUE);
        }
        break;

      case 'text':
      default:
        foreach ($checklists as $checklist) {
          $renderer = new Console($checklist, $this->logger, $options, $output);
          // The Console::renderer() doesn't return anything, it print directly to the console.
          $renderer->render(TRUE);
        }
        break;
    }

    return $out;
  }

  /**
   * Run All Site Audit reports.
   *
   * @option format
   *   Format you which the report is to be in (html, text, json, markdown)
   * @option detail
   *   Show details when no issues found for the check.
   * @option bootstrap
   *   Wrap the report in HTML with Bootstrap derived styles. Forces --format=html
   * @usage site_audit:audit-all
   *   Run all Site Audit reports
   *
   * @command site_audit:all
   * @aliases audit-all
   * @aliases aa
   *
   * @usage audit-all
   *   run all reports
   * @usage audit-all --skip=block,status
   *  skip the block and status reports
   */
  public function audit_all($options = ['skip' => 'none', 'format' => 'text', 'detail' => FALSE, 'bootstrap' => FALSE]) {
    return $this->audit('all', $options);
  }

  /**
   * Take Drupal\Core\StringTranslation\TranslatableMarkup and return the string.
   *
   * @param $message
   * @param array $context
   *
   * @return string
   */
  public function interpolate($message, array $context = []) {
    if (get_class($message) == 'Drupal\Core\StringTranslation\TranslatableMarkup') {
      return $message->render();
    }
    return $message;
  }

  /**
   * @hook interact site_audit:audit
   * @param $input
   * @param $output
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function interactSiteAudit($input, $output) {
    $boot_manager = Drush::bootstrapManager();
    if (empty($input->getArgument('report'))) {
      $checklists = $this->getReports($boot_manager->hasBootstrapped(DRUSH_BOOTSTRAP_DRUPAL_FULL));
      $choices = [
        'all' => $this->interpolate($this->t('All')),
      ];
      foreach ($checklists as $checklist) {
        $choices[$checklist['id']] = $this->interpolate($checklist['name']);
      }
      $choice = $this->io()->choice($this->t("Choose a report to run"), $choices, 'all');
      $input->setArgument('report', $choice);
    }
  }

  /**
   * Get a list of all the report definitions
   */
  public function getReports($include_bootstrapped_types = FALSE) {
    $checklistDefinitions = $this->auditChecklistManager->getDefinitions();
    return $checklistDefinitions;
  }

  /**
   * List of all available reports.
   *
   * @field-labels
   *   report_id: Report ID
   *   report_name: Report Name
   *   report_description : Report Description
   *   check_id: Check ID
   *   check_name: Check Name
   *   check_description: Check Description
   * @default-fields report_id,report_name,check_name,check_description
   *
   * @command site_audit:list
   * @aliases audit-list
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function list() {
    $checklistDefinitions = $this->auditChecklistManager->getDefinitions();
    $checkDefinitions = $this->auditCheckManager->getDefinitions();
    $rows = [];
    $report_id = '';
    foreach ($checklistDefinitions as $report) {
      if ($report_id != $report['id'] && !empty($report_id)) {
        $rows[] = [];
      }
      $thisReport = $this->auditCheckManager->createInstance($report['id']);
      $checks = $thisReport->getChecksList();
      foreach ($checks as $check) {
        $rows[] = [
          'report_id' => $report_id == $report['id'] ? '' : $report['id'],
          'report_name' => $report_id == $report['id'] ? '' : $report['name'],
          'report_description' => $report_id == $report['id'] ? '' : $report['description'],
          'check_id' => $checkDefinitions[$check]['id'],
          'check_name' => $checkDefinitions[$check]['name'],
          'check_description' => $checkDefinitions[$check]['description'],
        ];
        $report_id = $report['id'];
      }
    }
    return new RowsOfFields($rows);
  }

}
