<?php

namespace Drupal\eca_push_framework\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca\Plugin\PluginUsageInterface;
use Drupal\eca_push_framework\Event\DirectPush;
use Drupal\eca_push_framework\Event\EcaPushFrameworkEvents;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Plugin implementation of the ECA Push Framework Events.
 *
 * @EcaEvent(
 *   id = "eca_push_framework",
 *   deriver = "Drupal\eca_push_framework\Plugin\ECA\Event\EcaPushFrameworkEventDeriver"
 * )
 */
class EcaPushFrameworkEvent extends EventBase implements PluginUsageInterface {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $events = [];
    $events['eca_push_framework_direct_push'] = [
      'label' => 'Direct PUsh',
      'event_name' => EcaPushFrameworkEvents::DIRECT_PUSH,
      'event_class' => DirectPush::class,
    ];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $values = [
      'event_id' => '',
    ];
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event ID'),
      '#default_value' => $this->configuration['event_id'],
      '#description' => $this->t('The event ID to trigger this event directly.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['event_id'] = $form_state->getValue('event_id');
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();
    return trim($configuration['event_id']);
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesForWildcard(Event $event, string $event_name, string $wildcard): bool {
    if ($event instanceof DirectPush) {
      return ($event->getEventId() === $wildcard);
    }
    return parent::appliesForWildcard($event, $event_name, $wildcard);
  }

  /**
   * {@inheritdoc}
   */
  public function pluginUsed(Eca $eca, string $id): void {}

}
