<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;

/**
 * Checks the last time cron has run.
 *
 * @SecurityCheck(
 *   id = "vendor_directory",
 *   title = @Translation("Vendor Directory Location"),
 *   description = @Translation("Checks the vendor directory is outside webroot."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Vendor directory is outside webroot."),
 *   failure_message = @Translation("Vendor directory is not outside webroot."),
 *   help = {
 *     @Translation("Verify the vendor directory is located outside the webroot directory."),
 *   }
 * )
 */
class VendorDirectory extends SecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $outside = TRUE;

    $autoloader = DRUPAL_ROOT . '/vendor/autoload.php';

    if (file_exists($autoloader)) {
      $result = CheckResult::FAIL;
      $outside = FALSE;
    }

    $this->createResult($result, ['vendor_directory_location' => $outside]);
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
    if (isset($findings['vendor_directory_location']) && !$findings['vendor_directory_location']) {
      $paragraphs[] = $this->t("Vendor directory is not outside webroot.");
    }

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
