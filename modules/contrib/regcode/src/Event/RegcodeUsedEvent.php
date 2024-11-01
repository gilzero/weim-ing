<?php

declare(strict_types=1);

namespace Drupal\regcode\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

/**
 * Event that is fired when a regcode is used.
 */
class RegcodeUsedEvent extends Event {

  const EVENT_NAME = 'regcode.code_used';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The registration code.
   *
   * @var object
   */
  protected $regcode;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The account of the user logged in.
   * @param string $regcode
   *   The regcode.
   */
  public function __construct(UserInterface $user, object $regcode) {
    $this->user = $user;
    $this->regcode = $regcode;
  }

}
