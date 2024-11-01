<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\regcode\RegistrationCodeInterface;

// cspell:ignore lastused maxuses alphanum

/**
 * Tests the operation of hooks provided by the Regcode module.
 *
 * @group Regcode
 */
class RegcodeHookTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * The registration_code service.
   *
   * @var \Drupal\regcode\RegistrationCodeInterface
   */
  protected $registrationCode;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The generated registration code.
   *
   * @var \stdClass
   */
  protected $regcode;

  /**
   * Administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'regcode',
    'regcode_alterer',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Regcode module table in the database.
    $this->installSchema('regcode', ['regcode']);

    // Initialize the registration_code service.
    $this->registrationCode = $this->container->get('registration_code');

    // The hook implementations in the regcode_alterer test module will output
    // messages to the messenger. We will verify the operation of the hooks by
    // looking at these messages.
    $this->messenger = $this->container->get('messenger');

    // An admin user so we have someone to assign the registration code to.
    $this->adminUser = $this->setUpCurrentUser([
      'uid' => 1,
      'name' => 'username with spaces',
      'mail' => 'admin@test.example.com',
    ]);

    // Create a new registration code and set some values.
    $code = new \stdClass();
    $code->rid = 0;
    $code->uid = 1;
    $code->created = 0;
    $code->lastused = 0;
    $code->begins = 0;
    $code->expires = 0;
    $code->code = '';
    $code->is_active = 1;
    $code->maxuses = 1;
    $code->uses = 1;

    // Generate a code. This does not save it to the DB, so there is no
    // valid ->rid property yet.
    // Could perhaps use a data provider for multiple codes.
    $code->code = $this->registrationCode->generate(17, 'alphanum', FALSE);

    // Save code.
    $code->rid = $this->registrationCode->save($code, RegistrationCodeInterface::MODE_SKIP);
    if ($code->rid !== FALSE) {
      $this->regcode = $code;
      $this->messenger->addStatus("Created registration code ($code->code)");
    }
    else {
      $this->messenger->addWarning("Unable to create code ($code->code) as code already exists");
    }
  }

  /**
   * Tests that hook_regcode_used() was called properly.
   */
  public function testHookRegcodeUsed(): void {
    // Consume the registration code.
    $this->registrationCode->consumeCode($this->regcode->code, $this->adminUser->id());

    // Verify that regcode_alterer_regcode_used() was called by checking if
    // that method generated the expected message.
    $messages = $this->messenger->all();

    // Messages added in self:setUp():
    // - RegistrationCode::save() calls regcode_alterer_regcode_presave() which
    //   adds 1 status message.
    // - self::setUp() explicitly adds 1 more message.
    // Messages added in this test method:
    // - RegistrationCode::consumeCode() calls RegistrationCode::validate()
    //   which calls RegistrationCode::load() which adds 1 more status message.
    // - RegistrationCode::consumeCode() also calls
    //   regcode_alterer_regcode_used() which adds 1 last message.
    $this->assertCount(4, $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertEquals(
      "Thanks {$this->adminUser->getAccountName()}, the code '{$this->regcode->rid}' was used.",
      (string) $messages[MessengerInterface::TYPE_STATUS][3],
      var_export($messages, TRUE)
    );
  }

  /**
   * Tests that hook_regcode_presave() was called properly.
   */
  public function testHookRegcodePresave(): void {
    // Verify that regcode_alterer_regcode_presave() was called by checking if
    // that method generated the expected message.
    $messages = $this->messenger->all();

    // Messages added in self:setUp():
    // - RegistrationCode::save() calls regcode_alterer_regcode_presave() which
    //   adds 1 status message.
    // - self::setUp() explicitly adds 1 more message.
    $this->assertCount(2, $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertEquals(
      "The code '{$this->regcode->code}' was created.",
      (string) $messages[MessengerInterface::TYPE_STATUS][0],
      var_export($messages, TRUE)
    );
  }

  /**
   * Tests that hook_regcode_load() was called properly.
   */
  public function testHookRegcodeLoad(): void {
    // Load the registration code.
    $this->registrationCode->load($this->regcode->rid, []);

    // Verify that regcode_alterer_regcode_load() was called by checking if
    // that method generated the expected message.
    $messages = $this->messenger->all();

    // Messages added in self:setUp():
    // - RegistrationCode::save() calls regcode_alterer_regcode_presave() which
    //   adds 1 status message.
    // - self::setUp() explicitly adds 1 more message.
    // Messages added in this test method:
    // - RegistrationCode::load() calls regcode_alterer_regcode_load() which
    //   adds 1 last status message.
    $this->assertCount(3, $messages[MessengerInterface::TYPE_STATUS]);
    $this->assertEquals(
      "The code '{$this->regcode->rid}' was loaded.",
      (string) $messages[MessengerInterface::TYPE_STATUS][2],
      var_export($messages, TRUE)
    );
  }

}
