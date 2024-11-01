<?php

declare(strict_types=1);

namespace Drupal\security_review;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\security_review\Exception\NotImplementedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for handing a Security Check.
 */
abstract class SecurityCheckBase extends PluginBase implements SecurityCheckInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * State prefix used throughout.
   */
  protected string $statePrefix;

  /**
   * SecurityCheckBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\security_review\SecurityReview $securityReview
   *   The security review service.
   * @param \Drupal\security_review\SecurityReviewData $securitySettings
   *   The security_review.data service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected StateInterface $state,
    protected SecurityReview $securityReview,
    protected SecurityReviewData $securitySettings,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->statePrefix = 'security_review.check.' . $this->getPluginId() . '.';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    // @phpstan-ignore-next-line
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('security_review'),
      $container->get('security_review.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float {
    $this->doRun($cli);
    return 1;
  }

  /**
   * Perform the run.
   *
   * This is provided as a convenience to not have to worry about batching if
   * it is not needed. If you need to use batching, override run() instead.
   *
   * @param bool $cli
   *   If run should take cli in mind.
   *
   * @throws \Drupal\security_review\Exception\NotImplementedException
   */
  protected function doRun(bool $cli): void {
    throw new NotImplementedException('You need to override the doRun method if you want to use the default run() implementation.');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return (string) $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace(): string {
    return (string) $this->pluginDefinition['namespace'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values) {}

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return ['id' => $this->getPluginId()] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = array_merge(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMessage(int $status = 0): string {
    return (string) match ($status) {
      CheckResult::SUCCESS => $this->pluginDefinition['success_message'],
      CheckResult::FAIL => $this->pluginDefinition['failure_message'],
      CheckResult::INFO => $this->pluginDefinition['info_message'],
      CheckResult::WARN => $this->pluginDefinition['warning_message'],
      default => $this->t('Unexpected Error'),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp(): array {
    return [
      '#theme' => 'check_help',
      '#title' => $this->pluginDefinition['title'],
      '#paragraphs' => $this->pluginDefinition['help'],
    ];
  }

  /**
   * Creates a new CheckResult for this Check.
   *
   * @param int $result
   *   The result integer (see the constants defined in CheckResult).
   * @param array $findings
   *   The findings.
   * @param int|null $time
   *   The time the test was run.
   * @param array $hushed_findings
   *   The hushed findings.
   *
   * @return \Drupal\security_review\CheckResult
   *   The created CheckResult.
   */
  public function createResult(int $result, array $findings = [], int $time = NULL, array $hushed_findings = []): CheckResult {
    $checkResult = new CheckResult($this, $result, $findings, $time, $hushed_findings);
    $this->storeResult($checkResult);
    return $checkResult;
  }

  /**
   * {@inheritdoc}
   */
  public function lastResult(): array {
    // Get stored data from State system.
    $last_result = $this->getResult();

    // Check validity of stored data.
    $valid_result = is_int($last_result['result']) && $last_result['result'] >= CheckResult::SUCCESS && $last_result['result'] <= CheckResult::INFO;
    $valid_findings = is_array($last_result['findings']);
    $valid_time = is_int($last_result['time']) && $last_result['time'] > 0;

    // If invalid, return empty array.
    if (!$valid_result || !$valid_findings || !$valid_time) {
      return [];
    }

    return $last_result;
  }

  /**
   * Retrieve the current results.
   *
   * @return array
   *   Return array of current stored results
   */
  public function getResult(): array {
    return [
      'result' => $this->state->get($this->getStateVariableName('last_result.result')),
      'time' => $this->state->get($this->getStateVariableName('last_result.time')),
      'findings' => $this->state->get($this->getStateVariableName('last_result.findings')) ?: [],
      'hushed' => $this->state->get($this->getStateVariableName('last_result.hushed_findings')) ?: [],
    ];
  }

  /**
   * Stores a result in the state system.
   */
  public function storeResult(CheckResult $check): void {
    $findings = !empty($check->findings()) ? $check->findings() : [];
    $hushed_findings = !empty($check->hushedFindings()) ? $check->hushedFindings() : [];
    $this->state->setMultiple([
      $this->getStateVariableName('last_result.result') => $check->result(),
      $this->getStateVariableName('last_result.time') => $check->time(),
      $this->getStateVariableName('last_result.findings') => $findings,
      $this->getStateVariableName('last_result.hushed_findings') => $hushed_findings,
    ]);
  }

  /**
   * Helper function to produce a full state variable name.
   *
   * @param string $variableName
   *   The unique part of the variable for this module.
   *
   * @return string
   *   The full variable name to pass to the state service.
   */
  protected function getStateVariableName(string $variableName): string {
    return $this->statePrefix . $variableName;
  }

}
