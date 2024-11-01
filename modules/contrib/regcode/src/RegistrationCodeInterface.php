<?php

namespace Drupal\regcode;

// cspell:ignore hexadec alphanum

/**
 * The registration_code service.
 */
interface RegistrationCodeInterface {

  /**
   * Regcode validation error codes.
   *
   * @see ::validateCode()
   */
  const VALIDITY_NOT_EXISTING = 0;
  const VALIDITY_NOT_AVAILABLE = 1;
  const VALIDITY_TAKEN = 2;
  const VALIDITY_EXPIRED = 3;

  /**
   * Regcode validation error codes.
   *
   * @see ::save()
   */
  const MODE_REPLACE = 0;
  const MODE_SKIP = 1;

  /**
   * Regcode operation IDs.
   *
   * @see ::clean()
   */
  const CLEAN_TRUNCATE = 1;
  const CLEAN_INACTIVE = 3;
  const CLEAN_EXPIRED = 4;

  /**
   * Loads a registration code.
   *
   * Usage example:
   * @code
   *   regcode_load(1231); // Loads the regcode with rid=1231
   *   regcode_load(NULL, ['code'=>'foobar']); // Loads the "foobar" regcode
   * @endcode
   *
   * @param int|null $id
   *   The database primary key (rid).
   * @param array $conditions
   *   An associative array containing the search conditions.
   *
   * @return object|false
   *   The regcode object or FALSE if the code does not exist.
   */
  public function load(?int $id = NULL, array $conditions = []): object|false;

  /**
   * Validates a registration code.
   *
   * @param string $regcode
   *   The alphanumeric registration code.
   *
   * @return int|object
   *   An error code, or the loaded regcode.
   */
  public function validateCode(string $regcode): int|object;

  /**
   * Consumes a registration code and attributes it to a user.
   *
   * Calls hook_regcode_used() to allow other modules to react.
   *
   * @param string $regcode
   *   The registration code.
   * @param string|int $uid
   *   (optional) User id to assign the given code to.
   *
   * @return int|object
   *   An integer error code, or the updated registration code.
   */
  public function consumeCode(string $regcode, string|int $uid): int|object;

  /**
   * Saves a registration code in the database and calls hook_regcode_presave().
   *
   * @param object $code
   *   A regcode object (required fields are code, begins, expires, is_active,
   *   and maxuses.
   * @param int $action
   *   Action to perform when saving the code.
   *
   * @return int|false
   *   The regcode ID if the code was saved. Otherwise FALSE.
   */
  public function save(object $code, int $action = self::MODE_REPLACE): int|false;

  /**
   * Deletes registration codes.
   *
   * @param int $op
   *   The operation ID. One of:
   *   - RegistrationCodeInterface::CLEAN_TRUNCATE
   *   - RegistrationCodeInterface::CLEAN_INACTIVE
   *   - RegistrationCodeInterface::CLEAN_EXPIRED
   *   No other values are allowed.
   *
   * @return int|bool
   *   The number of deleted rows or FALSE if nothing happened or TRUE if tables
   *   were empty.
   */
  public function clean(int $op): int|bool;

  /**
   * Generates a registration code.
   *
   * @param int $length
   *   Length of the registration code.
   * @param string $output
   *   Allowed values are 'alpha', 'alphanum', 'hexadec', or 'numeric'.
   * @param bool $case
   *   TRUE to use only uppercase for the registration code.
   *
   * @return string
   *   The generated registration code.
   */
  public function generate(int $length, string $output, bool $case): string;

  /**
   * Regcode delete action.
   *
   * @param object $object
   *   The regcode object to delete.
   * @param array $context
   *   Currently unused.
   */
  public function deleteAction(object &$object, array $context = []): void;

  /**
   * Regcode activate action.
   *
   * @param object $object
   *   The regcode object to activate.
   * @param array $context
   *   Currently unused.
   */
  public function activateAction(object &$object, array $context = []): void;

  /**
   * Regcode deactivate action.
   *
   * @param object $object
   *   The regcode object to deactivate.
   * @param array $context
   *   Currently unused.
   */
  public function deactivateAction(object &$object, array $context = []): void;

  /**
   * Gets a list of terms from the registration code vocabulary.
   *
   * @return array
   *   An array of terms.
   */
  public function getVocabTerms(): array;

}
