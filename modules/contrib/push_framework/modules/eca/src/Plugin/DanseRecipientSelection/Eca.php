<?php

namespace Drupal\eca_push_framework\Plugin\DanseRecipientSelection;

use Drupal\push_framework\Plugin\DanseRecipientSelection\DirectPush;

/**
 * Plugin implementation of DANSE.
 *
 * @DanseRecipientSelection(
 *   id = "eca_recipient_selection_direct_push",
 *   deriver = "Drupal\eca_push_framework\Plugin\DanseRecipientSelection\EcaDeriver"
 * )
 */
class Eca extends DirectPush {

  /**
   * {@inheritdoc}
   */
  public function directPushChannelId(): string {
    return 'eca:' . $this->getPluginDefinition()['event_id'];
  }

}
