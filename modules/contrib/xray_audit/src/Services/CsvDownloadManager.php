<?php

namespace Drupal\xray_audit\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service to download a csv with the data.
 */
class CsvDownloadManager implements CsvDownloadManagerInterface {

  /**
   * Service "xray_audit.plugin_repository".
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Service "request_stack".
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritDoc}
   */
  public function downloadCsv(): bool {
    // Obtaining the current request.
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return FALSE;
    }

    // Obtaining the parameter download of the current query.
    $queryParam = $request->query->get('0');
    if (!empty($queryParam) && $queryParam == 'download') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function createCsv(array $csvData, array $headers, string $operation = ''): ?StreamedResponse {

    // phpcs:ignore
    // From: http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
    $response = new StreamedResponse(function () use ($headers, $csvData) {
      $handle = fopen('php://output', 'r+');
      if ($handle === FALSE) {
        return NULL;
      }

      fputcsv($handle, $headers);

      foreach ($csvData as $row) {
        fputcsv($handle, $row);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $operation . '-' . date('Y-m-d') . '.csv"');

    return $response->send();
  }

}
