<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;

/**
 * Checks the last time cron has run.
 *
 * @SecurityCheck(
 *   id = "trusted_hosts",
 *   title = @Translation("Trusted Hosts Set"),
 *   description = @Translation("Checks for trusted_host_patterns in settings.php."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Trusted hosts are set."),
 *   failure_message = @Translation("Trusted hosts are not set."),
 *   help = {
 *     @Translation("Often Drupal needs to know the URL(s) it is responding from in order to build full links back to itself (e.g. password reset links sent via email). Until you explicitly tell Drupal what full or partial URL(s) it should respond for it must dynamically detect it based on the incoming request, something that can be maliciously spoofed in order to trick someone into unknowingly visiting an attacker's site (known as a HTTP host header attack)."),
 *   }
 * )
 */
class TrustedHosts extends SecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::FAIL;
    $trusted_host_patterns_set = FALSE;
    $findings = [];
    $settings_php = $this->securitySettings->sitePath() . '/settings.php';

    if (!file_exists($settings_php)) {
      $this->createResult(CheckResult::INFO);
    }

    if (!empty(Settings::get('trusted_host_patterns'))) {
      $trusted_host_patterns_set = TRUE;
      $result = CheckResult::SUCCESS;
    }

    if ($result === CheckResult::FAIL) {
      // Provide information if the check failed.
      $findings['settings'] = $settings_php;
      $findings['trusted_host_patterns_set'] = $trusted_host_patterns_set;
    }

    $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    global $base_url;
    $output = $returnString ? '' : [];
    $paragraphs = [];

    $settings_php = $this->securitySettings->sitePath() . '/settings.php';
    $paragraphs[] = $this->t('This site is responding from the URL: :url.', [':url' => $base_url]);
    $paragraphs[] = $this->t('If the site has multiple URLs it can respond from you should whitelist host patterns with trusted_host_patterns in settings.php at @file.', ['@file' => $settings_php]);
    $paragraphs[] = new Link($this->t('Read more about HTTP Host Header attacks and setting trusted_host_patterns.'), Url::fromUri('https://www.drupal.org/node/1992030'));

    if ($returnString) {
      $output .= implode("", $paragraphs);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
      ];
    }

    return $output;
  }

}
