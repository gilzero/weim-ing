<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\danse\Entity\Notification;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Constructs a WebPush service object.
 */
class Push extends Base implements PushInterface {

  /**
   * Web push class.
   *
   * @var \Minishlink\WebPush\WebPush
   */
  protected WebPush $webPush;

  /**
   * {@inheritdoc}
   */
  public function defaultOptions(): array {
    $ttl = $this->getConfig()->get('ttl') ?: static::DEFAULT_OPTIONS['ttl'];
    $batch_size = $this->getConfig()->get('batch_size') ?: static::DEFAULT_OPTIONS['batch_size'];
    return [
      'TTL' => (int) $ttl,
      'topic' => $this->getConfig()->get('topic') ?: static::DEFAULT_OPTIONS['topic'],
      'batchSize' => (int) $batch_size,
      'urgency' => $this->getConfig()->get('urgency') ?: static::DEFAULT_OPTIONS['urgency'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isToken(string $value): bool {
    return $value === static::DANSE_TOKENS['topic'];
  }

  /**
   * {@inheritdoc}
   */
  public function sendNotification(array $push_data, bool $test = FALSE): void {

    // Initialise WebPush.
    $this->initWebPush($push_data);

    $uid = $push_data['entity_data']['uid'] ?: $this->getCurrentUser()->id();
    if ($test) {
      // Prepare subscription data.
      $push_data['uid'] = (int) $uid;
      $push_data['topic'] = static::TEST_ID;
      $push_data['danse_key'] = static::TEST_ID;
      $this->prepareSubscription($push_data, $push_data['danse_key'], (int) $uid, $test);
    }
    else {

      // Find unique danse key.
      $keys = $this->getDanseKey((int) $uid, $push_data);

      if (empty($keys)) {
        return;
      }

      foreach ($keys as $key) {

        // Take care of notification url.
        $danse_data = $this->getDanseData((int) $uid, $key, $push_data);
        $notification = $danse_data[$key]['notification'] ?? NULL;
        if ($notification && ($url = $push_data['entity_links']['entity']->getUrl())) {
          $oid = $notification instanceof Notification ? $notification->id() : $notification;
          $url->setOption('query', ['oid' => $oid]);
          $push_data['content']['url'] = $url->toString();
          $push_data['content']['id'] = $oid;
        }
        // Now define notification topic.
        $topic_value = $this->webPush->getDefaultOptions()['topic'];
        $is_token = $topic_value && $this->isToken($topic_value);
        if ($is_token) {
          $push_data['topic'] = $danse_data[$key]['topic'] ?? $topic_value;
        }
        else {
          $push_data['topic'] = $topic_value;
        }
        $push_data['danse_key'] = $key;
        $this->prepareSubscription($push_data, $key, (int) $uid, $test);
      }
    }

    // WebPush flush() method is actually executing queued subscriptions.
    /** @var \Minishlink\WebPush\MessageSentReport $report */
    foreach ($this->webPush->flush() as $report) {
      $this->manageReport($report, (int) $uid, $push_data, $test);
    }
  }

  /**
   * Get DANSE's unique key to match our subscription.
   *
   * @param int $uid
   *   Id of a user for whom subscriptions are fetched.
   * @param array<string, array<string>> $data
   *   The data to push with options.
   *
   * @return array
   *   Matched DANSE keys.
   */
  protected function getDanseKey(int $uid, array $data): array {
    $keys = [];
    $danse_data = $this->getUserData()->get('danse', $uid);
    if (!empty($danse_data)) {
      foreach ($danse_data as $danse_key => $value) {
        if (is_array($value) && isset($value[static::PROPERTY])) {
          foreach ($value[static::PROPERTY] as $subscription) {
            if ($subscription['danse_key'] === $danse_key) {
              $keys[$uid] = $danse_key;
            }
          }
        }
      }
    }
    return $keys;
  }

  /**
   * Init the WebPush class.
   *
   * @param array<string, array<string>> $push_data
   *   Associative array with mandatory push data.
   */
  protected function initWebPush(array $push_data): void {
    $keys = $this->getKeys();
    $public_key = $keys['public_key'] ?? NULL;
    $private_key = $keys['private_key'] ?? NULL;
    if (!$public_key || !$private_key) {
      $this->getLogger()->error('VAPID keys are not set.');
      return;
    }

    try {
      $auth = [
        'VAPID' => [
          'subject' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
          'publicKey' => $public_key,
          'privateKey' => $private_key,
        ],
      ];

      // Allow other modules to alter auth data.
      $this->moduleHandler->invokeAll('pf_notifications_vapid', [&$auth]);

      $this->webPush = new WebPush($auth, $push_data['options']);
      $this->webPush->setDefaultOptions($push_data['options']);
      $this->webPush->setReuseVAPIDHeaders(TRUE);
    }
    catch (\ErrorException $e) {
      $this->getLogger()->error($e->getMessage());
    }
  }

  /**
   * Get involved DANSe events.
   *
   * @param int $uid
   *   An id of a user for whom subscriptions are fetched.
   * @param string $danse_key
   *   DANSE's unique action key.
   * @param array<string, array<string, mixed>> $data
   *   Data to be pushed.
   *
   * @return array
   *   DANSE events entities, keyed by id.
   */
  protected function getEvents(int $uid, string $danse_key, array $data): array {
    $events = [];
    // Not the most elegant indeed, but no better option in tis context.
    $chunks = explode('-', $danse_key);
    try {
      $entity_id = $data['entity_data']['entity_id'] ?? NULL;
      /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
      $query = $this->entityTypeManager->getStorage('danse_event')->getQuery();

      $query->accessCheck(FALSE)
        ->condition('plugin', static::DANSE_MODULE)
        ->condition('processed', 1);
      if ($entity_id) {
        $query->condition('reference', $chunks[4] . '-' . $entity_id);
      }

      $events = $query->execute();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->getLogger()->error($e->getMessage());
    }
    return $events;
  }

  /**
   * Match notifications prepared for the user who posted entity.
   *
   * In this case we prevent and unset Notification,
   * especially this make sense with as per browser/device subscriptions.
   *
   * @param int $uid
   *   An id of a user for whom subscriptions are fetched.
   * @param string $event_id
   *   DANSE's event in question id.
   * @param array<string, array<string, mixed>> $data
   *   Data to be pushed.
   *
   * @return string|null
   *   DANSE Notification id or null.
   */
  protected function getNotification(int $uid, string $event_id, array $data): string|NULL {

    try {
      $notifications = $this->entityTypeManager->getStorage('danse_notification')->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->condition('event', $event_id)
        ->execute();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->getLogger()->error($e->getMessage());
    }

    if (!empty($notifications)) {
      return end($notifications);
    }
    return NULL;
  }

  /**
   * GEt additional DANSE data, such as topic and notification id.
   *
   * In this case we prevent and unset Notification,
   * especially this make sense with as per browser/device subscriptions.
   *
   * @param int $uid
   *   An id of a user for whom subscriptions are fetched.
   * @param string $danse_key
   *   DANSE's unique action key.
   * @param array<string, array<string, mixed>> $push_data
   *   Data to be pushed.
   *
   * @return array
   *   DANSE topic and notification id.
   */
  protected function getDanseData(int $uid, string $danse_key, array $push_data): array {

    $data = [];

    $events = $this->getEvents($uid, $danse_key, $push_data);
    if (!empty($events)) {
      foreach ($events as $event_id) {

        // Get actual DANSE notification.
        // $notification = $this->getNotification($uid, $event_id, $push_data);.
        $data[$danse_key] = [
          'notification' => $this->getNotification($uid, $event_id, $push_data),
        ];

        if ($this->getConfig()->get('topic') == '[danse_notification:topic]') {
          /** @var \Drupal\danse\Entity\Event $event */
          if ($event = $this->entityTypeManager->getStorage('danse_event')->load($event_id)) {
            $topic = $event->getTopic();
            if (ctype_alnum($topic)) {
              $data[$danse_key]['topic'] = $topic;
            }
          }
        }
        else {
          if (ctype_alnum($this->getConfig()->get('topic'))) {
            $data[$danse_key]['topic'] = $this->getConfig()->get('topic');
          }
        }
      }
    }
    return $data;
  }

  /**
   * Prepare the list of subscription to send.
   *
   * @param array<string, array<string, mixed>> $push_data
   *   Data to be pushed.
   * @param string $danse_key
   *   DANSE's unique action key.
   * @param int $uid
   *   An id of a user for whom subscriptions are fetched.
   * @param bool $test
   *   If it's a test notification found on config form.
   */
  protected function prepareSubscription(array &$push_data, string $danse_key, int $uid, bool $test = FALSE): void {

    $keys = $this->getKeys();
    $public_key = $keys['public_key'] ?? NULL;
    $push_data['public_key'] = $public_key ?? NULL;

    if ($test) {
      $subscriptions = $this->getUserData()->get('pf_notifications', $uid, static::TEST_ID) ?? [];
    }
    else {
      $key = $danse_key;
      $subscriptions = $this->getSubscriptions($uid, $key);
    }

    if (!empty($subscriptions)) {
      foreach ($subscriptions as $data) {
        $push_data['key'] = $data['key'];
        $push_data['token'] = $data['token'];
        $push_data['endpoint'] = $data['endpoint'];
        $push_data['danse_active'] = isset($data['danse_active']) ? (int) $data['danse_active'] : 0;
        $build = $this->buildSubscription($push_data);

        $notification_options = $this->webPush->getDefaultOptions();
        $notification_options['topic'] = (string) $push_data['topic'];

        try {
          $this->webPush->queueNotification(
            $build['subscription'],
            $build['payload'],
            $notification_options,
          );
        }
        catch (\ErrorException $e) {
          $this->loggerFactory->get('Push framework notifications')->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Build the WebPush Subscription notification.
   *
   * @param array<string, array<string>> $push_data
   *   Associative array with mandatory push data.
   *
   * @return array<string, string|Subscription>
   *   The Subscription notification array.
   */
  protected function buildSubscription(array $push_data): array {

    try {
      $web_subscription['subscription'] = Subscription::create([
        'endpoint' => $push_data['endpoint'],
        'publicKey' => $push_data['key'],
        'authToken' => $push_data['token'],
      ]);
    }
    catch (\ErrorException $e) {
      $this->getLogger()->error($e->getMessage());
    }

    // Check the URL to avoid redirection outside the site.
    $url_host = parse_url($push_data['content']['url'], PHP_URL_HOST);
    if ($url_host && $url_host !== $this->requestStack->getCurrentRequest()->getHost()) {
      $push_data['content']['url'] = '';
    }

    $web_subscription['payload'] = Json::encode($push_data['content']);
    return $web_subscription;
  }

  /**
   * Manage action on result event.
   *
   * @param \Minishlink\WebPush\MessageSentReport $report
   *   The result of the notification send.
   * @param int $uid
   *   An id of a user for whom subscriptions are fetched.
   * @param array<string, string|array<string>> $push_data
   *   Associative array with mandatory push data.
   * @param bool $test
   *   If it's a test notification found on config form.
   *
   * @see https://github.com/web-push-libs/web-push-php#server-errors
   */
  protected function manageReport(MessageSentReport $report, int $uid, array $push_data, bool $test = FALSE): void {

    $entity_links = $this->entityLinks($push_data['entity_data'], $report->getEndpoint());

    $message_params = [
      '@user' => $entity_links['user']->toString(),
      '%client' => $entity_links['client']->toString(),
      '@entity_type' => $entity_links['entity']->getUrl()->getOption('entity_type'),
      '@entity_link' => !$test ? $entity_links['entity']->toString() : $this->t('for the Test'),
    ];

    $debug = $this->getConfig()->get('debug');
    if ($debug && !$test) {

      $view_link = $entity_links['manage'];
      $view_link->getUrl()->setRouteParameter('arg_0', $uid);
      $view_link->getUrl()->setRouteParameter('arg_1', $push_data['danse_key']);
      $message_params['@view_link'] = $entity_links['manage']->toString();

      // Success info.
      if ($report->isSuccess()) {
        if (isset($entity_links['parent'])) {
          $message_params['@parent_entity_type'] = $entity_links['parent']->getUrl()->getOption('parent_entity_type');
          $message_params['@parent_link'] = $entity_links['parent']->toString();
          $message = $this->t('<p>Push notification delivered to @user, for a @entity_type @entity_link at @parent_entity_type @parent_link. Client: %client</p><p>@view_link.</p>', $message_params);
        }
        else {
          $message = $this->t('<p>Push notification delivered to @user, for a @entity_type @entity_link. Client: %client</p><p>@view_link.</p>', $message_params);
        }
        $this->getLogger()->notice($message);
      }
      // Error log.
      else {
        $message_params['@reason'] = $report->getReason();
        if (isset($entity_links['parent'])) {
          $message_params['@parent_entity_type'] = $entity_links['parent']->getUrl()->getOption('parent_entity_type');
          $message_params['@parent_link'] = $entity_links['parent']->toString();
          $message = $this->t('<p>Push notification failed for a user @user, for a @entity_type @entity_link at @parent_link.</p><p>Reason: @reason.</p><p>@view_link.</p>', $message_params);
        }
        else {
          $message = $this->t('<p>Push notification failed for a user @user, for a @entity_type @entity_link.</p><p>Reason: @reason.</p><p>@view_link.</p>', $message_params);
        }
        $this->getLogger()->error($message);
      }
    }

    // Subscription malformed or expired, remove from DANSE user data array.
    $error_codes = [400, 410];
    if (in_array($report->getResponse()->getStatusCode(), $error_codes) || $report->isSubscriptionExpired()) {
      $danse_key = (string) $push_data['danse_key'];
      $token = (string) $push_data['token'];
      $danse_active = isset($push_data['danse_active']) ? (int) $push_data['danse_active'] : 0;
      $this->deleteSubscription($uid, $danse_key, $token, $danse_active, $test);
      if ($debug && !$test) {
        $message_params['@deleted'] = 'was deleted';
        if (isset($entity_links['parent'])) {
          $message_params['@parent_link'] = $entity_links['parent']->toString();
          $message_params['@deleted'] = ' was deleted';
        }
        $message = $this->t('<p>Subscription fora a user: @user, for a @entity_type @entity_link @parent_link@deleted.</p><p>Reason: @reason.</p>', $message_params);
        $this->getLogger()->warning($message);
      }
    }
  }

}
