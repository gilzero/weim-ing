<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\regcode\RegistrationCodeInterface;

/**
 * Tests legacy regcode functionality.
 *
 * @group regcode
 * @group legacy
 */
class RegcodeLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'regcode',
    'system',
    'taxonomy',
    'text',
    'user',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('regcode', ['regcode']);
    // Needed to test regcode_get_vocab_terms().
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests the deprecation message for regcode_load_single().
   */
  public function testLoadSingle(): void {
    $this->expectDeprecation("regcode_load_single() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->load() instead. See https://www.drupal.org/node/3471716");
    $id = 1337;
    $conditions = [];
    $this->assertFalse(regcode_load_single($id, $conditions));
  }

  /**
   * Tests the deprecation message for regcode_code_validate().
   */
  public function testCodeValidate(): void {
    $this->expectDeprecation("regcode_get_vocab_terms() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->getVocabTerms() instead. See https://www.drupal.org/node/3471716");
    $regcode = "DoesNotExist";
    $this->assertIsInt(regcode_code_validate($regcode));
  }

  /**
   * Tests the deprecation message for regcode_code_consume().
   */
  public function testCodeConsume(): void {
    $this->expectDeprecation("regcode_code_consume() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->consumeCode() instead. See https://www.drupal.org/node/3471716");
    $regcode = "DoesNotExist";
    $uid = 1337;
    $this->assertIsInt(regcode_code_consume($regcode, $uid));
  }

  /**
   * Tests the deprecation message for regcode_save().
   */
  public function testSave(): void {
    $this->expectDeprecation("regcode_save() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->save() instead. See https://www.drupal.org/node/3471716");
    $code = new \stdClass();
    $action = RegistrationCodeInterface::MODE_REPLACE;
    $this->assertFalse(regcode_save($code, $action));
  }

  /**
   * Tests the deprecation message for regcode_clean().
   */
  public function testClean(): void {
    $this->expectDeprecation("regcode_get_vocab_terms() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->getVocabTerms() instead. See https://www.drupal.org/node/3471716");
    $op = RegistrationCodeInterface::CLEAN_TRUNCATE;
    $this->assertTrue(regcode_clean($op));
  }

  /**
   * Tests the deprecation message for regcode_generate().
   */
  public function testGenerate(): void {
    $this->expectDeprecation("regcode_get_vocab_terms() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->getVocabTerms() instead. See https://www.drupal.org/node/3471716");
    $length = 12;
    $output = 'alpha';
    $case = FALSE;
    $this->assertIsString(regcode_generate($length, $output, $case));
  }

  /**
   * Tests the deprecation message for regcode_delete_action().
   */
  public function testDeleteAction(): void {
    $this->expectDeprecation("regcode_delete_action() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->deleteAction() instead. See https://www.drupal.org/node/3471716");
    $object = new \stdClass();
    $object->rid = 2;
    $context = [];
    regcode_delete_action($object, $context);
  }

  /**
   * Tests the deprecation message for regcode_activate_action().
   */
  public function testActivateAction(): void {
    $this->expectDeprecation("regcode_activate_action() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->activateAction() instead. See https://www.drupal.org/node/3471716");
    $object = new \stdClass();
    $object->rid = 2;
    $context = [];
    regcode_activate_action($object, $context);
  }

  /**
   * Tests the deprecation message for regcode_deactivate_action().
   */
  public function testDeactivateAction(): void {
    $this->expectDeprecation("regcode_deactivate_action() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->deactivateAction() instead. See https://www.drupal.org/node/3471716");
    $object = new \stdClass();
    $object->rid = 2;
    $context = [];
    regcode_deactivate_action($object, $context);
  }

  /**
   * Tests the deprecation message for regcode_get_vocab_terms().
   */
  public function testGetVocabTerms(): void {
    $this->expectDeprecation("regcode_get_vocab_terms() is deprecated in regcode:2.0.0 and is removed from regcode:3.0.0. Use \Drupal::service('registration_code')->getVocabTerms() instead. See https://www.drupal.org/node/3471716");
    regcode_get_vocab_terms();
  }

}
