<?php

namespace Drupal\danse_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\danse\Service as DanseService;
use Drupal\danse_content\Plugin\Danse\Content;
use Drupal\user\UserDataInterface;

/**
 * Logger service to write messages and exceptions to an external service.
 */
class Service {

  use StringTranslationTrait;

  /**
   * The DANSE services.
   *
   * @var \Drupal\danse\Service
   */
  protected DanseService $service;

  /**
   * The user data storage service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The DANSE content plugin.
   *
   * @var \Drupal\danse_content\Plugin\Danse\Content|null
   */
  protected ?Content $plugin;

  /**
   * Constructs the DANSE content services.
   *
   * @param \Drupal\danse\Service $service
   *   The DANSE services.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data storage service.
   */
  public function __construct(DanseService $service, UserDataInterface $user_data) {
    $this->service = $service;
    $this->userData = $user_data;
  }

  /**
   * Gets the DANSE content plugin.
   *
   * @return \Drupal\danse_content\Plugin\Danse\Content|null
   *   The DANSE content plugin.
   */
  public function getPlugin(): ?Content {
    if (!isset($this->plugin)) {
      $plugin = $this->service->getPluginInstance('content');
      if ($plugin instanceof Content) {
        $this->plugin = $plugin;
      }
    }
    return $this->plugin;
  }

  /**
   * Gets the widget ID for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The widget ID.
   */
  public function widgetId(ContentEntityInterface $entity): string {
    return 'danse-widget-' . $entity->getEntityTypeId() . '-' . $entity->id();
  }

  /**
   * Builds the widget render array for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $resetCache
   *   Set to TRUE, if the cache for this widget should be reset.
   *
   * @return array
   *   The render array for the widget.
   */
  public function widget(ContentEntityInterface $entity, bool $resetCache = FALSE): array {
    $operations = [];

    $subscribeItems = [];
    $unsubscribeItems = [];
    foreach ($this->getPlugin()->subscriptionOperations($entity, $resetCache) as $subscriptionOperation) {
      if ($subscriptionOperation->isSubscribeMode()) {
        $subscribeItems[] = $subscriptionOperation;
      }
      else {
        $unsubscribeItems[] = $subscriptionOperation;
      }
    }
    if (!empty($subscribeItems) || !empty($unsubscribeItems)) {
      foreach ([$subscribeItems, $unsubscribeItems] as $items) {
        /**
         * @var \Drupal\danse_content\SubscriptionOperation $item
         */
        foreach ($items as $item) {
          $operations[] = $item->operationItem();
        }
      }
    }

    if (!empty($operations) && !$entity->isNew()) {
      return [
        '#theme_wrappers' => ['danse_content_subscription_wrapper'],
        '#id' => $this->widgetId($entity),
        '#dropbutton_type' => 'danse-widget-' . $entity->getEntityTypeId(),
        '#type' => 'operations',
        '#subtype' => 'danse__' . $entity->getEntityTypeId(),
        '#links' => $operations,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    return [];
  }

}
