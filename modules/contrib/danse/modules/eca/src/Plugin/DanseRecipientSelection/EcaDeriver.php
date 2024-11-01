<?php

namespace Drupal\eca_danse\Plugin\DanseRecipientSelection;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\Entity\Eca;
use Drupal\eca_danse\Event\DanseEvents;

/**
 * Deriver for ECA based recipient plugins.
 */
class EcaDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    // @phpstan-ignore-next-line
    if (\Drupal::moduleHandler()->moduleExists('eca')) {
      /** @var \Drupal\eca\Entity\Eca $eca */
      foreach (Eca::loadMultiple() as $id => $eca) {
        foreach ($eca->getUsedEvents() as $event) {
          if ($event->getPlugin()->eventName() === DanseEvents::RECIPIENT_SELECTION) {
            $this->derivatives[$id] = [
              'event_id' => $event->getConfiguration()['event_id'],
              'label' => $this->t('Recipients from ECA model @label: @event', [
                '@label' => $eca->label(),
                '@event' => $event->getLabel(),
              ]),
            ] + $base_plugin_definition;
          }
        }
      }
    }

    return $this->derivatives;
  }

}
