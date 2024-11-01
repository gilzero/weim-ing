<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Early exit to prevent extra load.
$nid = filter_input(INPUT_POST, 'nid', FILTER_VALIDATE_INT,
  ['options' => ['min_range' => 1]]
);
if (!$nid) {
  // Request type is not POST or variable is missing.
  exit();
}

// Catch exceptions when site is not configured or controller fails.
try {
  // Assumes module in modules/contrib/statistics, so three levels below root.
  chdir('../../..');

  $autoloader = require_once 'autoload.php';

  $request = Request::createFromGlobals();
  $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
  $kernel->boot();
  $container = $kernel->getContainer();

  $views = $container
    ->get('config.factory')
    ->get('statistics.settings')
    ->get('count_content_views');

  if ($views) {
    $container->get('request_stack')->push($request);
    $container->get('statistics.storage.node')->recordView($nid);
  }
}
catch (\Exception $e) {
  // Do nothing if there is PDO Exception or other failure.
}
