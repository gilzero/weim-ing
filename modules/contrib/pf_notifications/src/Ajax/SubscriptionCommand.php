<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Ajax;

use Drupal\Core\Ajax\DataCommand;

/**
 * Process web push subscription.
 *
 * @ingroup ajax
 */
class SubscriptionCommand extends DataCommand {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $render = parent::render();
    $render['command'] = 'pf_notifications_subscription';
    return $render;
  }

}
