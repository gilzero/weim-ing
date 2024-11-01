<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\danse_content\Topic\TopicInterface;
use Drupal\pf_notifications\Ajax\SubscriptionCommand;

/**
 * Web push subscriptions interface constructor.
 */
class Subscription extends Base implements SubscriptionInterface {

  /**
   * {@inheritdoc}
   */
  public function prepareLibraries(string $danse_key, int $danse_active = 0, ContentEntityInterface $entity = NULL): array {

    $data = [];

    // Get the VAPID Public Key to add it in JS notification subscription.
    $keys = $this->getKeys();
    $public_key = $keys['public_key'] ?? NULL;
    $private_key = $keys['private_key'] ?? NULL;

    // Check if the public key is set.
    if ($public_key && $private_key) {

      $test = $danse_key == static::TEST_ID;
      $uid = (int) $this->getCurrentUser()->id();
      $name = $this->getCurrentUser()->getDisplayName() ?: $this->getCurrentUser()->getAccountName();
      $entity_data = !$test ? $this->getEntityData($uid, $name, $entity) : [
        'uid' => $uid,
        'name' => $name,
      ];

      // Collect some data from DANSE notification here to send it to JS.
      $data = [
        'public_key' => $public_key,
        'serviceWorkerUrl' => Url::fromRoute('pf_notifications.service_worker')->toString(),
        'subscribeUrl' => Url::fromRoute(static::REST_ROUTE)->toString(),
        'danse_key' => $danse_key,
        'danse_active' => $danse_active,
        'subscribe' => NULL,
        'redirect' => NULL,
        'skip' => $this->getConfig()->get('skip') ? $this->getConfig()->get('skip') : 0,
        'entity_data' => $entity_data,
      ];

      // Set a few mandatory properties for test notification.
      if (!$test) {
        // Respect setting in config about (un) subscribe action notification.
        $danse_data = $this->userData->get('danse', $uid, $danse_key);
        $data['danse_active'] = $danse_active > 0 ? $danse_active : ($danse_data == 1 ? 1 : 0);
      }
    }
    else {
      $this->messenger->addError($this->t('@error', ['@error' => static::VAPID_ERROR]));
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptionResponse(int|array $op, string $danse_key, ContentEntityInterface $entity = NULL, AjaxResponse $response = NULL, int $danse_active = 0, bool $redirect = FALSE): AjaxResponse {

    if (!$response) {
      $response = new AjaxResponse();
    }

    $keys = $this->getKeys();
    $public_key = $keys['public_key'] ?? NULL;
    $private_key = $keys['private_key'] ?? NULL;

    // Check if the public and private keys are set.
    if (!$public_key && !$private_key) {
      $this->getLogger()->error($this->t('@error', ['@error' => static::VAPID_ERROR]));
      return $response;
    }

    // No entity, so it's for Test notification, prepare and return.
    if (!$entity) {
      $data = $this->prepareLibraries($danse_key);
      $this->operation($op, $data);
      $data['tokens'] = $this->tokens($this->getCurrentUser());
      return $this->commands($data, $response);
    }

    // Get all required data for front-end operations.
    $data = $this->prepareLibraries($danse_key, $danse_active, $entity);
    // Determine which operation we do.
    $this->operation($op, $data);
    // Prepare (any) redirect url.
    if ($redirect) {
      $this->redirect($entity, $data);
    }
    // Run commands and return ajax response.
    return $this->commands($data, $response, $entity);
  }

  /**
   * Process available data and generate some tokens.
   *
   * Note, most variables/dynamics is in front-end,
   * where push notification subscription must happen.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   A current user service.
   * @param \Drupal\Core\Entity\ContentEntityInterface<object>|null $entity
   *   Entity being a subscription subject. Typically, Node or Comment.
   * @param string|null $danse_key
   *   DANSE's unique action key.
   * @param \Drupal\Core\Entity\ContentEntityInterface<object>|null $parent_entity
   *   Parent enitty of an entity being a subscription subject.
   *    Typically, Node or parent Comment, for ar Comment.
   * @param \Drupal\danse_content\Topic\TopicInterface $topic
   *   DANSE's Topic interface.
   * @param array<string, array<string, string>> $subscriptions
   *   Existing subscriptions for a given, current user.
   *
   * @return array<string, string|bool>
   *   Array with all available token values.
   */
  public function tokens(AccountProxyInterface $user, ContentEntityInterface $entity = NULL, string $danse_key = NULL, ContentEntityInterface $parent_entity = NULL, TopicInterface $topic = NULL, array $subscriptions = []): array {

    $label_pattern = '[push-object:label]';
    $parent_label_pattern = '[push-object:parent_label]';
    $title_pattern = $this->getConfig()->get('title') ?: '"[push-object:label]" updates notifications!';
    $body_pattern = $this->getConfig()->get('body') ?: 'Relevant updates on the current post will notifications to your subscription in this browser/device.';
    $title = $this->getConfig()->get('title') ?: static::SUBSCRIBE_TITLE;
    $body = $this->getConfig()->get('body') ?: static::SUBSCRIBE_BODY;

    $push_object = [
      'label' => $entity ? $entity->label() : '[Content_Title]',
      'title' => $title,
      'body' => $body,
    ];
    if ($parent_entity) {
      $push_object['parent_label'] = $parent_entity->label();
    }

    $token_data = [
      'user' => $user,
      'push-object' => $push_object,
      'push_framework_source_plugin' => $this->danseService->getPlugin(),
      'push_framework_source_id' => NULL,
    ];

    // This might look as weird, considering doing tokens, but, in fact,
    // quite some logic related to a subscription and data must
    // resolve only in front-end. @see pf_notifications.subscribe.js.
    $raw = [
      '[push-object:label]',
      '[push-object:parent_label]',
      '[push-object:op]',
      '[push-object:op_push]',
      '[push-object:client]',
      '[push-object:clients]',
    ];

    $replace = [
      $entity ? $entity->label() : '[Content_Title]',
      $parent_entity ? $parent_entity->label() : '',
      '@op',
      '@op_push',
      '@client',
      '@clients',
    ];

    $title_raw = str_replace($raw, $replace, $title);
    $body_raw = str_replace($raw, $replace, $body);
    $clients = [];

    if ($danse_key) {

      $danse_content = $this->danseService->getPlugin();
      $chunks = explode('-', $danse_key);
      if (!$subscriptions) {
        $subscriptions = $this->getSubscriptions((int) $this->getCurrentUser()->id(), $danse_key);
      }

      if (is_array($subscriptions) && !empty($subscriptions)) {
        foreach (static::PUSH_SERVICES as $push_service => $label) {
          $match = str_replace('*.', '', $push_service);
          foreach ($subscriptions as $subscription) {
            if ($subscription['danse_key'] == $danse_key && str_contains($subscription['endpoint'], $match)) {
              $clients[$match] = $label;
            }
          }
        }
      }
    }

    $tokens = [
      'label' => Html::decodeEntities($this->token->replace($label_pattern, $token_data, ['clear' => TRUE])),
      'title' => Html::decodeEntities($this->token->replace($title_pattern, $token_data, ['clear' => TRUE])),
      'body' => Html::decodeEntities($this->token->replace($body_pattern, $token_data, ['clear' => TRUE])),
      // 'topic' => push_framework_source_plugin
      'op' => str_contains($title, '[push-object:op]') || str_contains($body, '[push-object:op]'),
      'op_push' => str_contains($title, '[push-object:op_push]') || str_contains($body, '[push-object:op_push]'),
      'op_client' => str_contains($title, '[push-object:client]') || str_contains($body, '[push-object:client]'),
      'op_clients' => str_contains($title, '[push-object:clients]') || str_contains($body, '[push-object:clients]'),
      'title_raw' => Html::decodeEntities($title_raw),
      'body_raw' => Html::decodeEntities($body_raw),
      'clients' => array_values($clients),
      'url' => $this->getConfig()->get('url') ?? Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      'icon' => $this->getConfig()->get('icon') ?? '',
    ];

    if (!$topic && $danse_key) {
      $topic_id = end($chunks);
      $topics = array_filter($danse_content->topics(), function ($topic) use ($topic_id) {
        return $topic->id() == $topic_id;
      }, ARRAY_FILTER_USE_BOTH);
      $topic = reset($topics);
      $tokens['danse_widget_label'] = $topic->operationLabel($entity, TRUE, $chunks[3]);
    }

    if ($parent_entity) {
      $tokens['parent_label'] = Html::decodeEntities($this->token->replace($parent_label_pattern, $token_data, ['clear' => TRUE]));
    }

    $context = [
      'service' => $this,
      'entity' => $entity,
      'danseKey' => $danse_key,
      'parent_entity' => $parent_entity,
      'topic' => $topic,
      'subscriptions' => $subscriptions,
    ];

    // Allow other modules to alter tokens.
    $this->moduleHandler->alter('pf_notifications_tokens', $tokens, $context);

    return $tokens + static::TOKENS;
  }

  /**
   * Subscribe/unsubscribe flag determination.
   *
   * @param int|array $op
   *   A current value for this danse entry in users_data.
   * @param array<string, array<string, mixed>> $data
   *   The data to push with options.
   */
  protected function operation(int|array $op, array &$data): void {

    switch ($op) {
      case static::DANSE_UNSUBSCRIBED:
        $data['subscribe'] = array_keys(static::TOKENS)[2];
        break;

      case static::DANSE_SUBSCRIBED:
        // 'subscribed';
        $data['subscribe'] = array_keys(static::TOKENS)[1];
        break;

      // A special handling for NO web push subscriptions,
      // but just triggering notifications.
      case static::NO_PUSH:
        $data['subscribe'] = [
          'skip' => TRUE,
        ];
        // Existing $subscription for the same entity.
        // Either in the same browser/device or different.
        // The final part is in JS where we must match
        // push notification subscription endpoint.
      default:
        $data['subscribe'] = $op;
        break;
    }
  }

  /**
   * Execute Ajax commands in order ot register push subscription in front-end.
   *
   * @param array<string, string|int|array<string, mixed>> $data
   *   The data array to send to front-end via ajax command.
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   Any existing ajax response object that we might hook on.
   * @param \Drupal\Core\Entity\ContentEntityInterface<object>|null $entity
   *   Entity being a subscription subject. Typically, Node or Comment.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response which executed our commands.
   */
  protected function commands(array $data, AjaxResponse $response, ContentEntityInterface $entity = NULL): AjaxResponse {

    if ($entity) {
      $data['widget_selector'] = $this->danseService->widgetId($entity);
      // Do NOT use $resetCache = TRUE here.
      $widget = $this->danseService->widget($entity);
      $this->getLinkIndex($widget, $data);
      /* $response->addCommand(new ReplaceCommand('#' . $data['widget_selector'], $data['widget'])); */
      $data['tokens'] = $this->tokens($this->getCurrentUser(), $entity, $data['danse_key']);
    }

    // Allow other modules to alter subscription data.
    if ($entity) {
      $this->moduleHandler->invokeAll('pf_notifications_subscription_data', $data);
    }

    // Add the same data into drupalSettings as well.
    $attachments['library'][] = 'pf_notifications/subscribe';
    $attachments['drupalSettings']['pf_notifications'] = $data;
    $response->addAttachments($attachments);

    // Deliver data to our JS.
    // @see js/pf_notifications.subscribe.js
    $response->addCommand(new SubscriptionCommand('', 'pf_notifications_subscription', $data));

    return $response;
  }

  /**
   * Prepare redirect url.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface<object> $entity
   *   Entity being a subscription subject. Typically, Node or Comment.
   * @param array<string, string|int|array<string, mixed>> $data
   *   The data object to send to front-end via ajax command.
   */
  protected function redirect(ContentEntityInterface $entity, array &$data): void {

    // Generate redirect url and set redirect command.
    $url_options = [
      'absolute' => TRUE,
    ];

    $parent_id = $data['entity_data']['parent_id'] ?? NULL;
    $parent_type = $data['entity_data']['parent_type'] ?? NULL;

    if ($parent_id && $parent_type) {
      $url_options['fragment'] = $entity->getEntityTypeId() . '-' . $entity->id();
      $data['redirect'] = Url::fromRoute('entity.' . $parent_type . '.canonical', [
        $parent_type => $parent_id,
      ], $url_options)->toString();
    }
    // This must be a Node (or any parent entity).
    else {
      try {
        $data['redirect'] = $entity->toUrl(NULL, $url_options)->toString();
      }
      catch (EntityMalformedException $e) {
        $this->getLogger()->error($e->getMessage());
      }
    }
  }

  /**
   * Execute Ajax commands in order ot register push subscription in front-end.
   *
   * @param array<string, mixed> $widget
   *   Render array, DANSE's subscription actions widget.
   * @param array<string, string|int|array<string, mixed>> $data
   *   The data object to send to front-end via ajax command.
   */
  protected function getLinkIndex($widget, array &$data): void {
    foreach ($widget['#links'] as $index => $link) {
      $route_params = $link['url']->getRouteParameters();
      if ($route_params['key'] == $data['danse_key']) {
        $data['widget_link_index'] = $index;
      }
    }
  }

}
