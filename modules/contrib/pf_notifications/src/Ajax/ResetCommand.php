<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Ajax;

use Drupal\Core\Ajax\DataCommand;

/**
 * Unregister Service worker.
 *
 * @ingroup ajax
 */
class ResetCommand extends DataCommand {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $render = parent::render();
    $render['command'] = 'pf_notifications_reset';
    return $render;
  }

}
