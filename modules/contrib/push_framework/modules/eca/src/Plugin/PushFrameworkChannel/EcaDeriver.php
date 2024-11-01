<?php

namespace Drupal\eca_push_framework\Plugin\PushFrameworkChannel;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\Entity\Eca;
use Drupal\eca_push_framework\Event\EcaPushFrameworkEvents;

/**
 * Deriver for ECA based push framework channels.
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
          if ($event->getPlugin()->eventName() === EcaPushFrameworkEvents::DIRECT_PUSH) {
            $this->derivatives[$id] = [
              'event_id' => $event->getConfiguration()['event_id'],
              'label' => $this->t('Direct Push with ECA model @label: @event', [
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
