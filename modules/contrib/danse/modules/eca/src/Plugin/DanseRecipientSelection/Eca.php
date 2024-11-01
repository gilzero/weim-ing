<?php

namespace Drupal\eca_danse\Plugin\DanseRecipientSelection;

use Drupal\danse\PayloadInterface;
use Drupal\danse\RecipientSelectionBase;
use Drupal\eca_danse\Event\DanseEvents;
use Drupal\eca_danse\Event\RecipientSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin implementation of the ECA based recipient selection.
 *
 * @DanseRecipientSelection(
 *   id = "eca_recipient_selection",
 *   deriver = "Drupal\eca_danse\Plugin\DanseRecipientSelection\EcaDeriver"
 * )
 */
class Eca extends RecipientSelectionBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(PayloadInterface $payload): array {
    $event = new RecipientSelection($this->getPluginDefinition()['event_id'], $payload);
    $this->eventDispatcher->dispatch($event, DanseEvents::RECIPIENT_SELECTION);
    return $event->getRecipientsAsList();
  }

}
