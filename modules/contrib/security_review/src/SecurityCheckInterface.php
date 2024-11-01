<?php

namespace Drupal\security_review;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for using a Security Check.
 */
interface SecurityCheckInterface extends PluginInspectionInterface, ConfigurableInterface {

  /**
   * Executes the check and returns the results.
   *
   * @param bool $cli
   *   If run should take cli in mind.
   * @param array $sandbox
   *   Sandbox that can be used to pass information from one invocation to the
   *   next in a batch.
   *
   * @return mixed
   *   Return a float between 0 and 1 to indicate completion level. If a float
   *   < 1 is returned, the plugin will be called again, with the sandbox
   *   restored to the contents from the previous run.
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float;

  /**
   * Returns a translated string for the check title.
   *
   * @return string
   *   Title of check.
   */
  public function getTitle(): string;

  /**
   * Returns a translated description for the check description.
   *
   * @return string
   *   Description of check.
   */
  public function getDescription(): string;

  /**
   * Returns a translated namespace for the check.
   *
   * @return string
   *   Namespace of check.
   */
  public function getNamespace(): string;

  /**
   * Returns the pass / fail message.
   *
   * @param int $status
   *   Status number of message to return.
   *
   * @return string
   *   Message string.
   */
  public function getStatusMessage(int $status): string;

  /**
   * Returns render array of help for the check.
   *
   * @return array
   *   Help text from check.
   */
  public function getHelp(): array;

  /**
   * Returns render array of details for the results.
   *
   * @param array $findings
   *   Array of findings from the check run to iterate over.
   * @param array $hushed
   *   Array of hushed findings.
   * @param bool $returnString
   *   A flag if the output should be a string. Used by Drush commands.
   *
   * @return array|string
   *   Details of the test run. Array if front end, string if commandline.
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string;

  /**
   * Sets the default configuration.
   */
  public function defaultConfiguration();

  /**
   * Returns the configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Updates the form state after validating the configuration form.
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Handles the processing of the config form.
   */
  public function submitConfigurationForm(array $values);

  /**
   * Returns the configuration of the check.
   *
   * @return array
   *   Configuration array.
   */
  public function getConfiguration(): array;

  /**
   * Updates the configuration object.
   */
  public function setConfiguration(array $configuration);

  /**
   * Last stored result of the check.
   */
  public function lastResult();

  /**
   * Stores a result in the state system.
   */
  public function storeResult(CheckResult $check): void;

}
