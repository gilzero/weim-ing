<?php

declare(strict_types=1);

namespace Drupal\security_review\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckInterface;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Drush\Commands\DrushCommands;

/**
 * Provides drush command for running security review module.
 */
class SecurityReviewCommands extends DrushCommands {

  /**
   * Security review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReviewService;

  /**
   * The security checks plugin manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * Constructs a SecurityReviewCommands object.
   *
   * @param \Drupal\security_review\SecurityReview $security_review
   *   Security review service.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   */
  public function __construct(SecurityReview $security_review, SecurityCheckPluginManager $checkPluginManager) {
    parent::__construct();
    $this->securityReviewService = $security_review;
    $this->checkPluginManager = $checkPluginManager;
  }

  /**
   * Run the Security Review checklist.
   *
   * @command security:review
   * @option store
   *   Write results to the database
   * @option log
   *   Log results of each check to watchdog, defaults to off
   * @option lastrun
   *   Do not run the checklist, just print last results
   * @option check
   *   Comma-separated list of specified checks to run. See README.txt for
   *    list of options
   * @option skip
   *   Comma-separated list of specified checks not to run. This takes
   *    precedence over --check
   * @option short
   *   Short result messages instead of full description (e.g. 'Text formats')
   * @option results
   *   Show the incorrect settings for failed checks.
   * @usage secrev
   *   Run the checklist and output the results
   * @usage secrev --store
   *   Run the checklist, store, and output the results
   * @usage secrev --lastrun
   *   Output the stored results from the last run of the checklist
   * @aliases secrev, security-review
   * @format table
   * @pipe-format csv
   * @fields-default message, status
   * @field-labels
   *   message: Message
   *   status: Status
   *
   * @return \Consolidation\AnnotatedCommand\CommandResult
   *   Row of results.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function securityReview(
    $options = [
      'store' => FALSE,
      'log' => FALSE,
      'lastrun' => FALSE,
      'check' => NULL,
      'skip' => NULL,
      'short' => FALSE,
      'results' => FALSE,
    ],
  ): CommandResult {
    $store = $options['store'];
    $log = $options['log'];
    $last_run = $options['lastrun'];
    $run_checks = $options['check'];
    $skip_checks = $options['skip'];
    $short_titles = $options['short'];
    $show_findings = $options['results'];

    // Set temporary logging.
    $log = in_array($log, [TRUE, 1, 'TRUE']);
    $this->securityReviewService->setLogging($log, TRUE);

    if (!empty($short_titles)) {
      $short_titles = TRUE;
    }
    else {
      $short_titles = FALSE;
    }

    $results = [];
    if (!$last_run) {
      // Do a normal security review run.
      $checks = [];
      $to_skip = [];

      // Fill the $checks array.
      if (!empty($run_checks)) {
        // Get explicitly specified checks.
        foreach (explode(',', $run_checks) as $check) {
          $checks[] = $this->getCheck($check);
        }
      }
      else {
        // Get the whole checklist.
        $checks = $this->checkPluginManager->getChecks();
      }

      // Mark checks listed after --skip for removal.
      if (!empty($skip_checks)) {
        foreach (explode(',', $skip_checks) as $skip_check) {
          $to_skip[] = $this->getCheck($skip_check);
        }
      }

      // If storing, mark skipped checks for removal.
      if ($store) {
        foreach ($checks as $check) {
          if (in_array($check->getPluginId(), array_keys($this->securityReviewService->getSkipped()))) {
            $to_skip[] = $check;
          }
        }
      }

      // Remove the skipped checks from $checks.
      foreach ($to_skip as $skip_check) {
        foreach ($checks as $key => $check) {
          if ($check->getPluginId() == $skip_check->getPluginId()) {
            unset($checks[$key]);
          }
        }
      }

      // If $checks is empty at this point, return with an error.
      if (empty($checks)) {
        throw new \Exception(t("No checks to run. Run 'drush help secrev' for option use or consult the drush section of API.txt for further help."));
      }

      // Run the checks.
      $this->securityReviewService->runChecks($checks, TRUE);
    }
    else {
      // Show the latest stored results.
      foreach ($this->checkPluginManager->getChecks() as $check) {
        $last_result = $check->lastResult();
        if ($last_result instanceof CheckResult) {
          $results[] = $last_result;
        }
      }
    }

    $exitCode = self::EXIT_SUCCESS;
    foreach ($results as $result) {
      if ($result->result() == CheckResult::FAIL) {
        // At least one check failed.
        $exitCode = self::EXIT_FAILURE;
        break;
      }
    }

    return CommandResult::dataWithExitCode(new RowsOfFields($this->formatResults($results, $short_titles, $show_findings)), $exitCode);
  }

  /**
   * Helper function to compile Security Review results.
   *
   * @param \Drupal\security_review\CheckResult[] $results
   *   An array of CheckResults.
   * @param bool $short_titles
   *   Whether to use short message (check title) or full check success or
   *   failure message.
   * @param bool $show_findings
   *   Whether to print failed check results.
   *
   * @return array
   *   The results of the security review checks.
   */
  private function formatResults(array $results, bool $short_titles = FALSE, bool $show_findings = FALSE): array {
    $output = [];

    foreach ($results as $result) {
      if ($result instanceof CheckResult) {
        $check = $result->check();
        $message = $short_titles ? $check->getTitle() : $result->resultMessage();
        $status = 'notice';

        // Set log level according to check result.
        switch ($result->result()) {
          case CheckResult::SUCCESS:
            $status = 'success';
            break;

          case CheckResult::FAIL:
            $status = 'failed';
            break;

          case CheckResult::WARN:
            $status = 'warning';
            break;

          case CheckResult::INFO:
            $status = 'info';
            break;
        }

        // Attach findings.
        if ($show_findings) {
          $findings = trim($result->check()->getDetails($result, [], TRUE));
          if ($findings != '') {
            $message .= "\n" . $findings;
          }
        }

        $output[$check->getPluginId()] = [
          'message' => $message,
          'status' => $status,
          'findings' => $result->findings(),
        ];
      }
    }

    return $output;
  }

  /**
   * Helper function for parsing input check name strings.
   *
   * @param string $check_name
   *   The check to get.
   *
   * @return \Drupal\security_review\SecurityCheckInterface|null
   *   The found Check.
   */
  private function getCheck(string $check_name): ?SecurityCheckInterface {
    // Default namespace is Security Review.
    $namespace = 'security_review';
    $title = $check_name;

    // Set namespace and title if explicitly defined.
    if (str_contains($check_name, ':')) {
      [$namespace, $title] = explode(':', $check_name);
    }

    // Return the found check if any.
    return $this->checkPluginManager->getCheck($namespace, $title);
  }

}
