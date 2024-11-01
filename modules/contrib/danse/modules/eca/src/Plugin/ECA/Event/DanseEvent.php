<?php

namespace Drupal\eca_danse\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca\Plugin\PluginUsageInterface;
use Drupal\eca_danse\Event\DanseEvents;
use Drupal\eca_danse\Event\RecipientSelection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Plugin implementation of the DANSE ECA Events.
 *
 * @EcaEvent(
 *   id = "danse",
 *   deriver = "Drupal\eca_danse\Plugin\ECA\Event\DanseEventDeriver",
 *   eca_version_introduced = "2.3.0"
 * )
 */
class DanseEvent extends EventBase implements PluginUsageInterface {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $events = [];
    $events['danse_recipient_selection'] = [
      'label' => 'Recipient Selection',
      'event_name' => DanseEvents::RECIPIENT_SELECTION,
      'event_class' => RecipientSelection::class,
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
  public function pluginUsed(Eca $eca, string $id): void {}

  /**
   * {@inheritdoc}
   */
  public function generateWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $configuration = $ecaEvent->getConfiguration();
    return isset($configuration['event_id']) ? trim($configuration['event_id']) : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesForWildcard(Event $event, string $event_name, string $wildcard): bool {
    if ($event instanceof RecipientSelection) {
      return ($event->getEventId() === $wildcard) || ($wildcard === '');
    }
    return FALSE;
  }

  /**
   * Gets the data.
   *
   * @param string $key
   *   The key.
   *
   * @return \Drupal\eca\Plugin\DataType\DataTransferObject|null
   *   The transfer object.
   */
  public function getData(string $key): ?DataTransferObject {
    $event = $this->getEvent();
    if ($event instanceof RecipientSelection) {
      if ($key === 'danse_recipients') {
        return $event->getRecipientsAsDto();
      }
    }
    return NULL;
  }

}
