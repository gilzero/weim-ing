<?php

namespace Drupal\danse;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase as CorePluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\Entity\Notification;
use Drupal\danse\Entity\NotificationAction;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\push_framework\Plugin\DanseRecipientSelection\DirectPushInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for DANSE plugins.
 */
abstract class PluginBase extends CorePluginBase implements PluginInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $bundleInfo;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The user data storage service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The list of recipient selection plugins.
   *
   * @var \Drupal\danse\RecipientSelectionInterface[]
   */
  protected array $recipientSelectionPlugins;

  /**
   * The DANSE query service.
   *
   * @var \Drupal\danse\Query
   */
  protected Query $query;

  /**
   * The DANSE services.
   *
   * @var \Drupal\danse\Service
   */
  protected Service $danse;

  /**
   * The subscription key parts.
   *
   * @var array
   */
  protected array $subscriptionKeyParts;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * PluginBase constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data storage service.
   * @param \Drupal\danse\RecipientSelectionManager $recipient_selection_manager
   *   The recipient selection plugin manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\danse\Query $query
   *   The DANSE query service.
   * @param \Drupal\danse\Service $danse
   *   The DANSE services.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tag invalidator.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, ConfigFactoryInterface $config_factory, UserDataInterface $user_data, RecipientSelectionManager $recipient_selection_manager, AccountProxyInterface $current_user, RouteMatchInterface $route_match, Query $query, Service $danse, CacheBackendInterface $cacheBackend, CacheTagsInvalidatorInterface $cacheTagsInvalidator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->configFactory = $config_factory;
    $this->userData = $user_data;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->query = $query;
    $this->danse = $danse;
    $this->cacheBackend = $cacheBackend;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;

    $this->recipientSelectionPlugins = [];
    $ids = $config_factory->get('danse.settings')->get('recipient_selection_plugin.' . $this->getPluginId());
    if (!empty($ids)) {
      foreach (explode(',', $ids) as $danse_plugin_id) {
        try {
          $this->recipientSelectionPlugins[] = $recipient_selection_manager->createInstance($danse_plugin_id);
        }
        catch (PluginException $e) {
          // This may fail, i.e. during enable/disable module.
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PluginInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('config.factory'),
      $container->get('user.data'),
      $container->get('plugin.manager.danse.recipient.selection'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('danse.query'),
      $container->get('danse.service'),
      $container->get('cache.default'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Builds the cache ID for an object of a plugin.
   *
   * @param string $id
   *   An id for which a cache ID is needed.
   *
   * @return string
   *   The cache ID.
   */
  protected function buildCacheId(string $id): string {
    return "danse:$this->pluginId:$id";
  }

  /**
   * {@inheritdoc}
   */
  final public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  final public function createNotifications(): void {
    foreach ($this->getUnprocessedEvents() as $event) {
      /**
       * @var \Drupal\danse\PayloadInterface $payload
       */
      if ($payload = $event->getPayload()) {
        $subscribers = $this->getSubscribers($event);
        $recipients = [];
        if ($event->isPush()) {
          foreach ($this->recipientSelectionPlugins as $recipientSelectionPlugin) {
            if ($recipientSelectionPlugin instanceof DirectPushInterface) {
              $channelPluginId = $recipientSelectionPlugin->directPushChannelId();
              if (Settings::get('danse_notification_delivery', TRUE)) {
                $result = $recipientSelectionPlugin->push($payload);
              }
              else {
                $result = ChannelPluginInterface::RESULT_STATUS_SUCCESS;
              }
              $success = $result === ChannelPluginInterface::RESULT_STATUS_SUCCESS;
              foreach ($this->createNotification($event, 'push', 1) as $notification) {
                if ($success) {
                  $notification->markDelivered();
                }
                $action = NotificationAction::create([
                  'notification' => $notification->id(),
                  'success' => $success,
                  'payload' => [
                    'channel plugin' => $channelPluginId,
                  ],
                ]);
                try {
                  $action->save();
                }
                catch (EntityStorageException $e) {
                  // @todo Log this exception.
                }
              }
            }
            else {
              foreach ($recipientSelectionPlugin->getRecipients($payload) as $uid) {
                if (!in_array($uid, $recipients, TRUE)) {
                  $recipients[] = $uid;
                }
              }
            }
          }
        }
        foreach ($subscribers as $subscriber) {
          $this->createNotification($event, 'subscription', $subscriber);
        }
        foreach ($recipients as $recipient) {
          if (!in_array($recipient, $subscribers, TRUE)) {
            $this->createNotification($event, 'push', $recipient);
          }
        }
      }
      $event->setProcessed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assertSubscriptionKey() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  final public function setSubscriptionKeyParts(array $parts): void {
    $this->subscriptionKeyParts = $parts;
  }

  /**
   * {@inheritdoc}
   */
  final public function subscriptionKey(string ...$parts): string {
    array_unshift($parts, $this->getPluginId());
    return implode('-', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedSubscriptions($roles): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(EventInterface $event): Url {
    return Url::fromRoute('<front>');
  }

  /**
   * Creates a new DANSE event entity.
   *
   * @param string $topic_id
   *   The topic.
   * @param string $label
   *   The label.
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   * @param bool $push
   *   Whether notifications should be pushed.
   * @param bool $force
   *   Whether push notifications should be enforced, even if successors.
   * @param bool $silent
   *   Whether the event should be silent, i.e. not receive any notifications.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The DANSE event entity, if it's possible to be created, NULL otherwise.
   */
  protected function createEvent(string $topic_id, string $label, PayloadInterface $payload, bool $push = TRUE, bool $force = FALSE, bool $silent = FALSE): ?EventInterface {
    // Early in the bootstrap, SAVE_NEW will not be defined yet. In that case,
    // DANSE should not log events yet. Also, if DANSE is paused, don't create
    // an event.
    if (!defined('SAVED_NEW') || $this->danse->isPaused()) {
      return NULL;
    }
    if (!$this->assertPayload($payload)) {
      // @todo Log "Invalid payload provided.".
      return NULL;
    }
    /**
     * @var \Drupal\danse\Entity\EventInterface $event
     */
    $event = Event::create([
      'plugin' => $this->getPluginId(),
      'topic' => $topic_id,
      'label' => $label,
      'payload' => $payload,
      'push' => $push,
      'force' => $force,
      'silent' => $silent,
    ]);
    try {
      $event->save();
    }
    catch (EntityStorageException $e) {
      // @todo Log the issue
      return NULL;
    }
    return $event;
  }

  /**
   * Determine subscribed users to the given event.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The event.
   *
   * @return int[]
   *   List of subscribed user IDs.
   */
  protected function getSubscribers(EventInterface $event): array {
    $uids = [];
    if ($payload = $event->getPayload()) {
      $references = $payload->getSubscriptionReferences($event);
      foreach ($references as $reference) {
        foreach ($this->userData->get('danse', NULL, $reference) as $uid => $flag) {
          if ($flag) {
            /** @var \Drupal\user\UserInterface $user */
            $user = User::load($uid);
            if ($user->isActive()) {
              $uids[] = $uid;
            }
          }
        }
      }
    }
    return array_unique($uids, SORT_NUMERIC);
  }

  /**
   * Creates all required notifications for the given event.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The event.
   * @param string $trigger
   *   One of "push" or "subscription".
   * @param int $uid
   *   The user ID of the recipient.
   *
   * @return \Drupal\danse\Entity\NotificationInterface[]
   *   An array of already existing notifications, or an array containing one
   *   new notification, or an empty array, if the payload doesn't exist or the
   *   given user doesn't have access to the associated entity.
   */
  protected function createNotification(EventInterface $event, string $trigger, int $uid): array {
    $user = User::load($uid);
    if (!$user || !$user->isActive()) {
      // Do not create notifications for deleted or blocked users.
      return [];
    }
    $payload = $event->getPayload();
    if ($payload === NULL || !$payload->hasAccess($uid)) {
      return [];
    }
    $existingNotifications = $this->query->findSimilarEventNotifications($event, $uid);
    if (!empty($existingNotifications) && !$event->isForce()) {
      // There is an existing notification which is undelivered, so we do not
      // create a new one.
      return $existingNotifications;
    }
    /**
     * @var \Drupal\danse\Entity\NotificationInterface $notification
     */
    $notification = Notification::create([
      'event' => $event,
      'trigger' => $trigger,
      'uid' => $uid,
    ]);
    try {
      $notification->save();
    }
    catch (EntityStorageException $e) {
      // @todo Log the issue.
      return [];
    }
    // Mark existing notifications as redundant.
    foreach ($existingNotifications as $existingNotification) {
      $existingNotification->setSuccessor($notification);
    }
    return [$notification];
  }

  /**
   * Returns a list of all unprocessed events belonging to this plugin.
   *
   * @return \Drupal\danse\Entity\EventInterface[]
   *   The list of unprocessed events.
   */
  protected function getUnprocessedEvents(): array {
    try {
      /**
       * @var \Drupal\danse\Entity\EventInterface[] $events
       */
      $events = $this->entityTypeManager->getStorage('danse_event')
        ->loadByProperties([
          'plugin' => $this->getPluginId(),
          'processed' => 0,
        ]);
      ksort($events);
      return $events;
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
    return [];
  }

}
