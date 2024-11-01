<?php

declare(strict_types=1);

namespace Drupal\regcode;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\regcode\Event\RegcodeUsedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// phpcs:ignore Drupal.Commenting.InlineComment.SpacingAfter
// cspell:ignore lastused hexadec alphanum abcdefghijklmnopqrstuvwqyz

/**
 * The registration_code service.
 */
class RegistrationCode implements RegistrationCodeInterface {

  /**
   * A configuration object with regcode.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs an RegistrationCode service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger.factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   * @param \Drupal\Component\Datetime\TimeInterface $timeService
   *   The datetime.time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event_dispatcher service.
   */
  public function __construct(
    protected Connection $connection,
    protected LoggerChannelFactoryInterface $loggerFactory,
    ConfigFactoryInterface $config_factory,
    protected TimeInterface $timeService,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EventDispatcherInterface $dispatcher,
  ) {
    $this->config = $config_factory->get('regcode.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function load(?int $id = NULL, array $conditions = []): object|false {
    // Build the query.
    $query = $this->connection->select('regcode')
      ->fields('regcode', [
        'rid', 'uid', 'created', 'lastused', 'begins',
        'expires', 'code', 'is_active', 'maxuses', 'uses',
      ])
      ->range(0, 1);

    // Allow mixed search parameters.
    if (!empty($id)) {
      $query->condition('rid', $id);
    }
    else {
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }
    }

    // Run the query and grab a single regcode.
    $regcode = $query->execute()->fetchObject();
    if (!$regcode) {
      return FALSE;
    }

    // Entity loaders expect arrays of objects. entity_load() and
    // this function both invoke the hook below.
    $reg_codes = [$regcode->rid => $regcode];
    $this->moduleHandler->invokeAll('regcode_load', [$reg_codes]);

    return (object) $reg_codes[$regcode->rid];
  }

  /**
   * {@inheritdoc}
   */
  public function validateCode(string $regcode): int|object {
    // Load the code.
    $code = $this->load(NULL, ['code' => trim($regcode)]);

    // Check validity.
    if ($code === FALSE) {
      return RegistrationCodeInterface::VALIDITY_NOT_EXISTING;
    }
    if ($code->uses >= $code->maxuses && $code->maxuses !== '0') {
      return RegistrationCodeInterface::VALIDITY_TAKEN;
    }
    if (!$code->is_active) {
      return RegistrationCodeInterface::VALIDITY_NOT_AVAILABLE;
    }
    if (!empty($code->begins) && $code->begins > $this->timeService->getRequestTime()) {
      return RegistrationCodeInterface::VALIDITY_NOT_AVAILABLE;
    }
    if (!empty($code->expires) && $code->expires < $this->timeService->getRequestTime()) {
      return RegistrationCodeInterface::VALIDITY_EXPIRED;
    }

    return $code;
  }

  /**
   * {@inheritdoc}
   */
  public function consumeCode(string $regcode, string|int $uid): int|object {
    $code = $this->validateCode($regcode);

    // Check the code validated, otherwise return the error code.
    if (!is_object($code)) {
      // This is an integer error code from validateCode().
      return $code;
    }

    // We now know $code is an object.
    $code->uses++;

    // Mark the code inactive if it's used up.
    $active = 1;
    if ($code->maxuses != 0 && $code->uses >= $code->maxuses) {
      $active = 0;
    }

    // Use the code.
    $this->connection->update('regcode')
      ->fields([
        'uses' => $code->uses,
        'lastused' => $this->timeService->getRequestTime(),
        'uid' => $uid,
        'is_active' => $active,
      ])
      ->condition('rid', $code->rid)
      ->execute();

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    $user->regcode = $code;

    // Triggers hook_regcode_used().
    $hook = 'regcode_used';
    $this->moduleHandler->invokeAllWith($hook, function (callable $hook, string $module) use ($code, $user) {
      $hook($code, $user);
    });

    // Dispatch event to let other modules know that a regcode has been used.
    $event = new RegcodeUsedEvent($user, $code);
    $this->dispatcher->dispatch($event, $event::EVENT_NAME);

    return $code;
  }

  /**
   * {@inheritdoc}
   */
  public function save(object $code, int $action = RegistrationCodeInterface::MODE_REPLACE): int|false {
    // Set $rid.
    $rid = FALSE;

    // Sanity check.
    if (empty($code) || empty($code->code)) {
      return FALSE;
    }

    // Triggers hook_regcode_presave().
    $hook = 'regcode_presave';
    $this->moduleHandler->invokeAllWith($hook, function (callable $hook, string $module) use ($code) {
      $hook($code);
    });

    // Insert mode.
    if ($action == RegistrationCodeInterface::MODE_REPLACE) {
      $this->connection->delete('regcode')
        ->condition('code', $code->code)
        ->execute();
    }

    // Build the query and fetch result.
    $isExist = $this->connection->select('regcode')
      ->fields('regcode', ['rid'])
      ->condition('code', $code->code)
      ->execute()
      ->fetchObject();

    // Check if already exists.
    if (empty($isExist) || $isExist->rid === FALSE) {
      // Insert.
      $rid = $this->connection->insert('regcode')
        ->fields([
          'created'   => $this->timeService->getRequestTime(),
          'begins'    => empty($code->begins) ? NULL : (int) $code->begins,
          'expires'   => empty($code->expires) ? NULL : (int) $code->expires,
          'code'      => Html::escape($code->code),
          'is_active' => $code->is_active ?? 1,
          'maxuses'   => $code->maxuses ? (int) $code->maxuses : 1,
        ])
        ->execute();
    }

    return (int) $rid;
  }

  /**
   * {@inheritdoc}
   */
  public function clean(int $op): int|bool {
    switch ($op) {
      case RegistrationCodeInterface::CLEAN_TRUNCATE:
        $this->connection->truncate('regcode')->execute();
        $result = TRUE;
        break;

      case RegistrationCodeInterface::CLEAN_EXPIRED:
        $result = $this->connection->delete('regcode')
          ->condition('expires', $this->timeService->getRequestTime(), '<')
          ->execute();
        break;

      case RegistrationCodeInterface::CLEAN_INACTIVE:
        $result = $this->connection->delete('regcode')
          ->condition('is_active', 0)
          ->execute();
        break;

      default:
        $result = FALSE;
        break;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(int $length, string $output, bool $case): string {
    static $seeded = FALSE;

    // Possible seeds.
    $outputs['alpha']    = 'abcdefghijklmnopqrstuvwqyz';
    $outputs['numeric']  = '0123456789';
    $outputs['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
    $outputs['hexadec']  = '0123456789abcdef';

    // Choose seed.
    if (isset($outputs[$output])) {
      $output = $outputs[$output];
    }

    // Seed generator (only do this once per invocation).
    if (!$seeded) {
      [$usec, $sec] = explode(' ', microtime());
      $seed = $sec + (int) ($usec * 100000);
      mt_srand($seed);
      $seeded = TRUE;
    }

    // Generate.
    $str = '';
    $output_count = strlen($output);
    for ($i = 0; $length > $i; $i++) {
      $str .= $output[mt_rand(0, $output_count - 1)];
    }
    if ($case) {
      $str = strtoupper($str);
    }

    return $str;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAction(object &$object, array $context = []): void {
    $this->connection->delete('regcode')
      ->condition('rid', $object->rid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function activateAction(object &$object, array $context = []): void {
    $this->connection->update('regcode')
      ->fields(['is_active' => 1])
      ->condition('rid', $object->rid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deactivateAction(object &$object, array $context = []): void {
    $this->connection->update('regcode')
      ->fields(['is_active' => 0])
      ->condition('rid', $object->rid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabTerms(): array {
    $tree  = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->config->get('regcode_vocabulary'));
    $terms = [];
    foreach ($tree as $term) {
      $terms[$term->tid] = $term->name;
    }
    return $terms;
  }

}
