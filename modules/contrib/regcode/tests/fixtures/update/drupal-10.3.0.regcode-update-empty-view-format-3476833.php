<?php

/**
 * @file
 * Contains database additions to drupal-9.4.0.bare.standard.php.gz.
 *
 * This fixture enables the regcode module by setting system configuration
 * values in the {config} and {key_value} tables, then adds the regcode
 * View from Regcode 2.0.0 to the {config} table.
 *
 * This fixture is intended for use in testing regcode_update_10200().
 *
 * @see https://www.drupal.org/project/regcode/issues/3476833
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the regcode DB schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'regcode',
    'value' => 'i:10100;',
  ])
  ->execute();

// Update core.extension to enable regcode.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
// phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
$extensions = unserialize($extensions);
$extensions['module']['regcode'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Install the Regcode configuration.
// This is taken from regcode-2.0.0.
$config = [
  'regcode_optional' => FALSE,
  'regcode_generate_format' => 'alpha',
  'regcode_generate_case' => FALSE,
  'regcode_field_title' => 'Registration Code',
  'regcode_field_description' => 'Please enter your registration code.',
];
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'regcode.settings',
    'data' => serialize($config),
  ])
  ->execute();

// Load the View from regcode-2.0.0.
$config = Yaml::decode(file_get_contents(__DIR__ . '/views.view.regcode-2.0.0.yml'));
// Save View in the database.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'views.view.regcode',
    'data' => serialize($config),
  ])
  ->execute();
