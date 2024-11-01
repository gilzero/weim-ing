<?php

namespace Drupal\xray_audit\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Creates the CSV to download and checks if the route is the download one.
 */
interface CsvDownloadManagerInterface {

  /**
   * Check the download parameter.
   *
   * @return bool
   *   Whether the Csv has to be downloaded or not.
   */
  public function downloadCsv(): bool;

  /**
   * Check the download parameter.
   *
   * @param array $csvData
   *   CSV column's data.
   * @param array $headers
   *   CSV's headers.
   * @param string $operation
   *   Operation.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse|null
   *   Whether the Csv has to be downloaded or not.
   */
  public function createCsv(array $csvData, array $headers, string $operation = ''): ?StreamedResponse;

}
