<?php

declare(strict_types=1);

namespace Drupal\security_review;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A class containing static methods regarding the module's configuration.
 */
class SecurityReview {

  use DependencySerializationTrait;
  use LoggerChannelTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Name to use for state variable where we record our last run.
   */
  const STATE_NAME_LAST_RUN = 'security_review.last_run';

  /**
   * Temporary logging setting.
   *
   * @var bool
   */
  protected static ?bool $temporaryLogging = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * The state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The security checks plugin manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * Constructs a SecurityReview instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user, SecurityCheckPluginManager $checkPluginManager) {
    // Store the dependencies.
    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('security_review.settings');
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->checkPluginManager = $checkPluginManager;
  }

  /**
   * Runs an array of checks.
   *
   * @param \Drupal\security_review\SecurityCheckInterface[] $checks
   *   The array of Checks to run.
   * @param bool $cli
   *   Whether to call runCli() instead of run().
   */
  public function runChecks(array $checks, bool $cli = FALSE): void {
    foreach ($checks as $check) {
      if (!$this->isCheckSkipped($check->getPluginId())) {
        $sandbox = [];
        do {
          $finished = $check->run(TRUE, $sandbox);
        } while ($finished < 1);
      }
    }
  }

  /**
   * Stores an array of CheckResults.
   *
   * @param \Drupal\security_review\CheckResult[] $results
   *   The CheckResults to store.
   */
  public function storeResults(array $results): void {
    foreach ($results as $result) {
      $result->check()->storeResult($result);
    }
  }

  /**
   * Returns true if logging is enabled, otherwise returns false.
   *
   * @return bool
   *   A boolean indicating whether logging is enabled.
   */
  public function isLogging(): bool {
    // Check for temporary logging.
    if (static::$temporaryLogging !== NULL) {
      return static::$temporaryLogging;
    }

    return $this->config->get('log') === TRUE;
  }

  /**
   * Returns the last time Security Review has been run.
   *
   * @return int
   *   The last time Security Review has been run.
   */
  public function getLastRun(): int {
    return $this->state->get(self::STATE_NAME_LAST_RUN, 0);
  }

  /**
   * Returns the IDs of the stored untrusted roles.
   *
   * @return string[]
   *   Stored untrusted roles' IDs.
   */
  public function getUntrustedRoles(): array {
    return $this->config->get('untrusted_roles') ?? [];
  }

  /**
   * Returns the array of skipped checks.
   *
   * @return string[]
   *   Stored untrusted roles' IDs.
   */
  public function getSkipped(): array {
    return $this->config->get('skipped') ?? [];
  }

  /**
   * Returns the array for a specific check settings.
   *
   * @param string $check_name
   *   Name of the check to store.
   */
  public function getCheckSettings(string $check_name): array {
    return $this->config->get($check_name) ?? [];
  }

  /**
   * Is specific check skipped.
   *
   * @param string $check_name
   *   Name of check.
   *
   * @return array[]
   *   Skipped info.
   */
  public function isCheckSkipped(string $check_name): array {
    $skipped_array = $this->config->get('skipped');
    if (array_key_exists($check_name, $skipped_array)) {
      return $skipped_array[$check_name];
    }
    return [];
  }

  /**
   * Enables the check. Has no effect if the check was not skipped.
   *
   * @param string $check_name
   *   Name of check.
   */
  public function enable(string $check_name): void {
    if ($this->isCheckSkipped($check_name)) {
      $skipped = $this->config->get('skipped');
      unset($skipped[$check_name]);
      $this->setSkipped($skipped);
    }
  }

  /**
   * Marks the check as skipped.
   *
   * @param string $check_name
   *   Name of check.
   */
  public function skip(string $check_name): void {
    if (!$this->isCheckSkipped($check_name)) {
      $current_skipped = $this->config->get('skipped');
      $skip_check = [
        $check_name =>
          [
            'skipped' => TRUE,
            'skipped_by' => $this->currentUser->id() ?? 1,
            'skipped_on' => time(),
          ],
      ];

      $this->setSkipped(array_merge($current_skipped, $skip_check));
    }
  }

  /**
   * Sets the 'logging' flag.
   *
   * @param bool $logging
   *   The new value of the 'logging' setting.
   * @param bool $temporary
   *   Whether to set only temporarily.
   */
  public function setLogging(bool $logging, bool $temporary = FALSE): void {
    if (!$temporary) {
      $this->config->set('log', $logging);
      $this->config->save();
    }
    else {
      static::$temporaryLogging = ($logging == TRUE);
    }
  }

  /**
   * Sets the 'last_run' value.
   *
   * @param int $last_run
   *   The new value for 'last_run'.
   */
  public function setLastRun(int $last_run): void {
    $this->state->set(self::STATE_NAME_LAST_RUN, $last_run);
  }

  /**
   * Stores the given 'untrusted_roles' setting.
   *
   * @param string[] $untrusted_roles
   *   The new untrusted roles' IDs.
   */
  public function setUntrustedRoles(array $untrusted_roles): void {
    $this->config->set('untrusted_roles', $untrusted_roles);
    $this->config->save();
  }

  /**
   * Stores the given 'skipped' setting.
   *
   * @param string[] $skipped_checked
   *   The new skipped checks.
   */
  public function setSkipped(array $skipped_checked): void {
    $this->config->set('skipped', $skipped_checked);
    $this->config->save();
  }

  /**
   * Stores the potential custom config for a specific check.
   *
   * @param string $check_name
   *   Name of the check to store.
   * @param string[] $values
   *   Values of the check to store.
   */
  public function setCheckSettings(string $check_name, array $values): void {
    $this->config->set($check_name, $values);
    $this->config->save();
  }

  /**
   * Logs a check result.
   *
   * @param \Drupal\security_review\CheckResult|null $check
   *   The result to log.
   */
  public function logCheckResult(CheckResult $check = NULL): void {

    if ($this->isLogging()) {
      if ($check === NULL) {
        $context = [
          '@check' => $check->check()->getTitle(),
          '@namespace' => $check->check()->getNamespace(),
        ];
        $this->getLogger('security_review')->log(RfcLogLevel::CRITICAL, '@check of @namespace produced a null result', $context);
        return;
      }

      // Fallback log message.
      $level = RfcLogLevel::NOTICE;
      $message = '@name check invalid result';

      // Set log message and level according to result.
      switch ($check->result()) {
        case CheckResult::SUCCESS:
          $level = RfcLogLevel::INFO;
          $message = '@name check succeeded';
          break;

        case CheckResult::FAIL:
          $level = RfcLogLevel::ERROR;
          $message = '@name check failed';
          break;

        case CheckResult::WARN:
          $level = RfcLogLevel::WARNING;
          $message = '@name check raised a warning';
          break;

        case CheckResult::INFO:
          $level = RfcLogLevel::INFO;
          $message = '@name check returned info';
          break;
      }

      $context = ['@name' => $check->check()->getTitle()];
      $this->getLogger('security_review')->log($level, $message, $context);
    }
  }

  /**
   * Deletes orphaned check data.
   */
  public function cleanStorage(): void {
    // Get list of check configuration names.
    $orphaned = $this->configFactory->listAll('security_review.check.');

    // Remove items that are used by the checks.
    foreach ($this->checkPluginManager->getChecks() as $check) {
      $key = array_search('security_review.check.' . $check->getPluginId(), $orphaned);
      if ($key !== FALSE) {
        unset($orphaned[$key]);
      }
    }

    // Delete orphaned configuration data.
    foreach ($orphaned as $config_name) {
      $config = $this->configFactory->getEditable($config_name);
      $config->delete();
    }
  }

  /**
   * Stores information about the server into the State system.
   */
  public function setServerData(): void {
    if (!static::isServerPosix() || PHP_SAPI === 'cli') {
      return;
    }
    // Determine web server's uid and groups.
    $uid = posix_getuid();
    $groups = posix_getgroups();

    // Store the data in the State system.
    $this->state->set('security_review.server.uid', $uid);
    $this->state->set('security_review.server.groups', $groups);
  }

  /**
   * Returns whether the server is POSIX.
   *
   * @return bool
   *   Whether the web server is POSIX based.
   */
  public function isServerPosix(): bool {
    return function_exists('posix_getuid');
  }

  /**
   * Returns the UID of the web server.
   *
   * @return int
   *   UID of the web server's user.
   */
  public function getServerUid(): int {
    return $this->state->get('security_review.server.uid') ?? -1;
  }

  /**
   * Returns the GIDs of the web server.
   *
   * @return int[]
   *   GIDs of the web server's user.
   */
  public function getServerGids(): array {
    $groups = $this->state->get('security_review.server.groups');
    return $groups ?: [];
  }

}
