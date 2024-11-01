<?php

declare(strict_types=1);

namespace Drupal\security_review;

/**
 * Used to define the result of a Check.
 */
class CheckResult {

  const SUCCESS = 0;
  const FAIL = 1;
  const WARN = 2;
  const INFO = 3;

  /**
   * Stores the parent Check.
   *
   * @var \Drupal\security_review\SecurityCheckInterface
   */
  private SecurityCheckInterface $pluginCheck;

  /**
   * Stores the outcome of the check.
   *
   * @var int
   */
  private int $result;

  /**
   * Stores findings.
   *
   * @var array
   */
  private array $findings;

  /**
   * Stores the timestamp of the check run.
   *
   * @var int
   */
  private int $time;

  /**
   * Stores hushed findings.
   *
   * @var array
   */
  private array $hushedFindings;

  /**
   * Constructs an immutable CheckResult.
   *
   * @param \Drupal\security_review\SecurityCheckInterface $plugin_check
   *   The Check that created this result.
   * @param int $result
   *   The result integer (see the constants defined above).
   * @param array $findings
   *   The findings.
   * @param int|null $time
   *   The timestamp of the check run.
   * @param array $hushedFindings
   *   The hushed findings.
   */
  public function __construct(SecurityCheckInterface $plugin_check, int $result, array $findings, int $time = NULL, array $hushedFindings = []) {
    // Set the parent check.
    $this->pluginCheck = $plugin_check;

    // Set the result value.
    if ($result < self::SUCCESS || $result > self::INFO) {
      $result = self::INFO;
    }
    $this->result = $result;

    // Set the findings.
    $this->findings = $findings;

    // Set the hushed findings.
    $this->hushedFindings = $hushedFindings;

    // Set the timestamp.
    if (!is_int($time)) {
      $this->time = time();
    }
    else {
      $this->time = $time;
    }
  }

  /**
   * Returns the parent Check.
   *
   * @return \Drupal\security_review\SecurityCheckInterface
   *   The Check that created this result.
   */
  public function check(): SecurityCheckInterface {
    return $this->pluginCheck;
  }

  /**
   * Returns the outcome of the check.
   *
   * @return int
   *   The result integer.
   */
  public function result(): int {
    return $this->result;
  }

  /**
   * Returns the findings.
   *
   * @return array
   *   The findings. Contents of this depends on the actual check.
   */
  public function findings(): array {
    return $this->findings;
  }

  /**
   * Returns the hushed findings.
   *
   * @return array
   *   The hushed findings. Contents of this depends on the actual check.
   */
  public function hushedFindings(): array {
    return $this->hushedFindings;
  }

  /**
   * Returns the timestamp.
   *
   * @return int
   *   The timestamp the result was created on.
   */
  public function time(): int {
    return $this->time;
  }

  /**
   * Returns the result message.
   *
   * @return string
   *   The result message for this result.
   */
  public function resultMessage(): string {
    return $this->pluginCheck->getStatusMessage();
  }

}
