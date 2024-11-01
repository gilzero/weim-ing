<?php

namespace Drupal\raven;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LogLevel as PsrLogLevel;
use Sentry\Breadcrumb;
use Sentry\Severity;

/**
 * Maps various classes of log levels.
 */
enum LogLevel {

  case Emergency;
  case Alert;
  case Critical;
  case Error;
  case Warning;
  case Notice;
  case Info;
  case Debug;

  /**
   * Returns a LogLevel from an arbitrary integer or string.
   */
  public static function fromLevel(mixed $level): self {
    return match ($level) {
      RfcLogLevel::EMERGENCY, PsrLogLevel::EMERGENCY => self::Emergency,
      RfcLogLevel::ALERT, PsrLogLevel::ALERT => self::Alert,
      RfcLogLevel::CRITICAL, PsrLogLevel::CRITICAL => self::Critical,
      RfcLogLevel::ERROR, PsrLogLevel::ERROR => self::Error,
      RfcLogLevel::WARNING, PsrLogLevel::WARNING => self::Warning,
      RfcLogLevel::NOTICE, PsrLogLevel::NOTICE => self::Notice,
      RfcLogLevel::INFO, PsrLogLevel::INFO => self::Info,
      RfcLogLevel::DEBUG, PsrLogLevel::DEBUG => self::Debug,
      default => self::Info,
    };
  }

  /**
   * Returns the log level configuration options.
   *
   * @return array<string, TranslatableMarkup>
   *   The log level configuration options.
   */
  public static function getOptions(): array {
    foreach (self::cases() as $level) {
      $options[$level->getPsrLogLevel()] = $level->getLabel();
    }
    return $options;
  }

  /**
   * Migrates log level config from off-by-one RFC integers to PSR booleans.
   *
   * @param array<int, int> $old_levels
   *   Array of numeric log level configs.
   *
   * @return array<string, bool>
   *   Array of log level configs.
   */
  public static function migrateLogLevels(array $old_levels): array {
    foreach (self::cases() as $level) {
      $levels[$level->getPsrLogLevel()] = !empty($old_levels[$level->getRfcLogLevel() + 1]);
    }
    return $levels;
  }

  /**
   * Returns the Sentry breadcrumb level.
   */
  public function getBreadcrumbLevel(): string {
    return match ($this) {
      self::Emergency => Breadcrumb::LEVEL_FATAL,
      self::Alert => Breadcrumb::LEVEL_FATAL,
      self::Critical => Breadcrumb::LEVEL_FATAL,
      self::Error => Breadcrumb::LEVEL_ERROR,
      self::Warning => Breadcrumb::LEVEL_WARNING,
      self::Notice => Breadcrumb::LEVEL_INFO,
      self::Info => Breadcrumb::LEVEL_INFO,
      self::Debug => Breadcrumb::LEVEL_DEBUG,
    };
  }

  /**
   * Returns the PSR-3 log level.
   */
  public function getPsrLogLevel(): string {
    return match ($this) {
      self::Emergency => PsrLogLevel::EMERGENCY,
      self::Alert => PsrLogLevel::ALERT,
      self::Critical => PsrLogLevel::CRITICAL,
      self::Error => PsrLogLevel::ERROR,
      self::Warning => PsrLogLevel::WARNING,
      self::Notice => PsrLogLevel::NOTICE,
      self::Info => PsrLogLevel::INFO,
      self::Debug => PsrLogLevel::DEBUG,
    };
  }

  /**
   * Returns the RFC 5424 log level.
   */
  public function getRfcLogLevel(): int {
    return match ($this) {
      self::Emergency => RfcLogLevel::EMERGENCY,
      self::Alert => RfcLogLevel::ALERT,
      self::Critical => RfcLogLevel::CRITICAL,
      self::Error => RfcLogLevel::ERROR,
      self::Warning => RfcLogLevel::WARNING,
      self::Notice => RfcLogLevel::NOTICE,
      self::Info => RfcLogLevel::INFO,
      self::Debug => RfcLogLevel::DEBUG,
    };
  }

  /**
   * Returns the Sentry severity.
   */
  public function getSeverity(): Severity {
    return match ($this) {
      self::Emergency => Severity::fatal(),
      self::Alert => Severity::fatal(),
      self::Critical => Severity::fatal(),
      self::Error => Severity::error(),
      self::Warning => Severity::warning(),
      self::Notice => Severity::info(),
      self::Info => Severity::info(),
      self::Debug => Severity::debug(),
    };
  }

  /**
   * Returns the translatable label.
   */
  public function getLabel(): TranslatableMarkup {
    return RfcLogLevel::getLevels()[$this->getRfcLogLevel()];
  }

  /**
   * Returns TRUE if this log level is enabled.
   *
   * @param array<string, bool> $levels
   *   The log levels configuration.
   */
  public function isEnabled(array $levels): bool {
    return !empty($levels[$this->getPsrLogLevel()]);
  }

}
