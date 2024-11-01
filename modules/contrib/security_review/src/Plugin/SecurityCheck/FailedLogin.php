<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks for abundant failed logins.
 *
 * @SecurityCheck(
 *   id = "failed_logins",
 *   title = @Translation("Failed logins"),
 *   description = @Translation("Checks for abundant failed logins."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("No failed login attempts from same IP."),
 *   failure_message = @Translation("Failed login attempts from the same IP. These may be a brute-force attack to gain access to your site."),
 *   info_message = @Translation("Failed login attempts - Dblog module not installed."),
 *   help = {
 *     @Translation("Failed login attempts from the same IP may be an artifact of a malicious user attempting to brute-force their way onto your site as an authenticated user to carry out nefarious deeds."),
 *   }
 * )
 */
class FailedLogin extends SecurityCheckBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    // If dblog is not enabled return with hidden INFO.
    if (!$this->moduleHandler->moduleExists('dblog')) {
      $this->createResult(CheckResult::INFO);
    }
    else {
      $result = CheckResult::SUCCESS;
      $findings = [];
      $last_result = $this->lastResult();

      // Prepare the query.
      $query = $this->database->select('watchdog', 'w');
      $query->fields('w', [
        'severity',
        'type',
        'timestamp',
        'message',
        'variables',
        'hostname',
      ]);
      $query->condition('type', 'user')
        ->condition('severity', RfcLogLevel::NOTICE)
        ->condition('message', 'Login attempt failed for %user.');
      if (!empty($last_result) && $last_result['time']) {
        // Only check entries that got recorded since the last run of the check.
        $query->condition('timestamp', $last_result['time'], '>=');
      }

      // Execute the query.
      $db_result = $query->execute();

      // Count the number of failed logins per IP.
      $entries = [];
      $user = '';
      foreach ($db_result as $row) {
        $user = unserialize($row->variables, ['allowed_classes' => FALSE])['%user'];
        $ip = $row->hostname;
        $entry_for_ip = &$entries[$ip];

        if (!isset($entry_for_ip)) {
          $entry_for_ip = 0;
        }
        $entry_for_ip++;
      }

      // Filter the IPs with more than 10 failed logins.
      if (!empty($entries)) {
        foreach ($entries as $ip => $count) {
          if ($count > 10) {
            $findings[] = $ip . ':' . $user ?: 'failure to get user';
          }
        }
      }

      if (!empty($findings)) {
        $result = CheckResult::FAIL;
      }

      $this->createResult($result, $findings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];
    $paragraphs[] = $this->t('The following IPs were observed with an abundance of failed login attempts.');

    if ($returnString) {
      $output = $this->t('Suspicious IP addresses:');
      $output .= ":\n";
      foreach ($findings as $ip) {
        $output .= "\t" . $ip . "\n";
      }
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $findings,
      ];
    }

    return $output;
  }

}
