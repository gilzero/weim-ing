<?php

namespace Drupal\danse;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides DANSE related services to be executed during cron runs.
 */
class Cron {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The DANSE service.
   *
   * @var \Drupal\danse\Service
   */
  protected Service $danse;

  /**
   * The DANSE configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Cron constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\danse\Service $danse
   *   The DANSE service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Service $danse, ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->danse = $danse;
    $this->config = $config_factory->get('danse.settings');
  }

  /**
   * Prune events, notifications and actions according to settings.
   *
   * @param array|null $batch
   *   The batch configuration.
   */
  public function prune(array $batch = NULL): void {
    try {
      $eventStorage = $this->entityTypeManager->getStorage('danse_event');
      $notificationStorage = $this->entityTypeManager->getStorage('danse_notification');
      $actionStorage = $this->entityTypeManager->getStorage('danse_notification_action');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
      return;
    }
    foreach ($this->danse->getPluginInstances() as $id => $plugin) {
      $type = $this->config->get('prune.' . $id . '.type');
      if ($type === 'all') {
        continue;
      }
      $value = $this->config->get('prune.' . $id . '.value');
      $query = $eventStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('plugin', $id);
      if ($type === 'records') {
        $query2 = clone $query;
        $min_row = $query2
          ->range($value - 1, 1)
          ->sort('id', 'DESC')
          ->execute();
        if (empty($min_row)) {
          continue;
        }
        $query->condition('id', $min_row, '<');
      }
      else {
        $timestamp = strtotime('now -' . $value . ' ' . $type);
        $query->condition('created', $timestamp, '<');
      }

      $eventIds = $query->execute();
      if (!empty($eventIds)) {
        $notificationIds = $notificationStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('event', $eventIds, 'IN')
          ->execute();
        if (!empty($notificationIds)) {
          $actionIds = $actionStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('notification', $notificationIds, 'IN')
            ->execute();
          if (!empty($actionIds)) {
            if ($batch === NULL) {
              foreach ($actionIds as $actionId) {
                if ($action = $actionStorage->load($actionId)) {
                  try {
                    $action->delete();
                  }
                  catch (EntityStorageException $e) {
                    // @todo Log this exception.
                  }
                }
              }
            }
            else {
              $batch['operations'][] = [
                'danse_batch_prune',
                [$id, 'danse_notification_action', $actionIds],
              ];
            }
          }
          if ($batch === NULL) {
            foreach ($notificationIds as $notificationId) {
              if ($notification = $notificationStorage->load($notificationId)) {
                try {
                  $notification->delete();
                }
                catch (EntityStorageException $e) {
                  // @todo Log this exception.
                }
              }
            }
          }
          else {
            $batch['operations'][] = [
              'danse_batch_prune',
              [$id, 'danse_notification', $notificationIds],
            ];
          }
        }
        if ($batch === NULL) {
          foreach ($eventIds as $eventId) {
            if ($event = $eventStorage->load($eventId)) {
              try {
                $event->delete();
              }
              catch (EntityStorageException $e) {
                // @todo Log this exception.
              }
            }
          }
        }
        else {
          $batch['operations'][] = [
            'danse_batch_prune',
            [$id, 'danse_event', $eventIds],
          ];
        }
      }
    }
    if ($batch !== NULL && !empty($batch['operations'])) {
      batch_set($batch);
      batch_process();
    }
  }

}
