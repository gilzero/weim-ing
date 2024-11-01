<?php

declare(strict_types=1);

namespace Drupal\regcode\Drush\Commands;

use Drupal\regcode\RegistrationCodeInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

// cspell:ignore alphanum hexadec

/**
 * Drush 12+ commands for the Registration Codes module.
 *
 * Generates registration codes, validates registration codes, and consumes
 * registration codes.
 */
final class RegcodeDrushCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs the RegcodeDrushCommands object.
   *
   * @param \Drupal\regcode\RegistrationCodeInterface $registrationCode
   *   The registration_code service.
   */
  public function __construct(
    protected RegistrationCodeInterface $registrationCode,
  ) {
    parent::__construct();
  }

  /**
   * Validates a regcode.
   */
  #[CLI\Command(name: 'regcode:validate', aliases: ['regcode-validate'])]
  #[CLI\Help(description: 'Validates a registration code.')]
  #[CLI\Argument(name: 'regcode', description: 'The alphanumeric string registration code.')]
  #[CLI\Usage(name: 'drush regcode:validate [regcode]', description: 'Validates the given registration code.')]
  public function validateCode(string $regcode): void {
    if (!empty($regcode)) {
      $code = $this->registrationCode->validateCode($regcode);
      if (is_object($code)) {
        $this->output->writeln(dt('Registration code (@code) is valid', [
          '@code' => $regcode,
        ]));
      }
      else {
        $this->output->writeln(dt('User entered invalid registration code (@code)', [
          '@code' => $regcode,
        ]));
      }
    }
  }

  /**
   * Consumes a regcode and attributes it to a user.
   *
   * Calls hook_regcode_used() to allow other modules to react.
   *
   * @param string $regcode
   *   The registration code.
   * @param string|int $uid
   *   (optional) User id to assign the given code to.
   */
  #[CLI\Command(name: 'regcode:consume', aliases: ['regcode-consume'])]
  #[CLI\Help(description: 'Consumes a registration code and attributes it to a user.')]
  #[CLI\Argument(name: 'regcode', description: 'The string registration code.')]
  #[CLI\Argument(name: 'uid', description: 'The uid assigned.')]
  #[CLI\Usage(name: 'drush regcode:validate [regcode] [uid]', description: 'Assigns the registration code to the user and marks it as used.')]
  public function consumeCode(string $regcode, string|int $uid): void {
    $string = $this->registrationCode->consumeCode($regcode, $uid);
    // @todo We need to print a message for feedback.
    // @return int|object
    // An integer error code, or the updated regcode.
  }

  /**
   * Generates a registration code.
   *
   * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
   */
  #[CLI\Command(name: 'regcode:generate', aliases: ['regcode-generate'])]
  #[CLI\Help(description: 'Generates a registration code.')]
  #[CLI\Argument(name: 'regcode', description: 'The string registration code.')]
  #[CLI\Option(name: 'length', description: 'Length of the registration code.')]
  #[CLI\Option(name: 'output', description: "Allowed values are 'alpha', 'alphanum', 'hexadec', or 'numeric'.")]
  #[CLI\Option(name: 'case', description: 'If present, use only uppercase for the regcode.')]
  #[CLI\Option(name: 'case', description: 'If present, use only uppercase for the regcode.')]
  #[CLI\Option(name: 'qty', description: 'Quantity of codes to generate.')]
  #[CLI\Option(name: 'active', description: 'If code is active upon creation.')]
  #[CLI\Option(name: 'maxuses', description: 'Maximum number of uses for each registration code.')]
  #[CLI\Usage(name: 'drush regcode:generate', description: 'Creates and returns a registration code.')]
  #[CLI\Usage(name: 'drush regcode:generate --length=12 --output=alpha --case --qty=1', description: 'Creates and returns qty registration codes.')]
  public function generate(?string $regcode = NULL, array $options = ['length' => 12, 'output' => 'alpha', 'case' => FALSE, 'qty' => 1, 'active' => TRUE, 'maxuses' => 1]): void {
    $code = new \stdClass();
    $code->is_active = (bool) $options['active'];
    $code->maxuses = (int) $options['maxuses'];

    // Start creating codes.
    for ($i = 0; $i < (int) $options['qty']; $i++) {
      $code->code = $regcode;

      // Generate a code.
      if (empty($code->code) || $options['qty'] > 1) {
        $code->code = $this->registrationCode->generate((int) $options['length'], (string) $options['output'], (bool) $options['case']);
      }

      // Save code.
      if ($this->registrationCode->save($code, RegistrationCodeInterface::MODE_SKIP)) {
        $this->output->writeln(dt('Created registration code (%code)', [
          '%code' => $code->code,
        ]));
      }
      else {
        $this->output->writeln(dt('Unable to create code (%code) as code already exists', [
          '%code' => $code->code,
        ]));
      }
    }
  }

}
