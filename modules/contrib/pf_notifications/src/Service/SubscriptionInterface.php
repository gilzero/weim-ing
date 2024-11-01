<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Web push subscription interface constructor.
 */
interface SubscriptionInterface {

  /**
   * SubscriptionResource rest generic route.
   *
   * @var string
   */
  public const REST_ROUTE = 'rest.pf_notifications_subscription.POST';

  public const DANSE_UNSUBSCRIBED = 0;

  public const DANSE_SUBSCRIBED = 1;

  public const NO_PUSH = 2;

  /**
   * Default subscription notification title.
   *
   * @var string
   */
  public const SUBSCRIBE_TITLE = 'Updated subscription for the current content.';

  /**
   * Default subscription notification body.
   *
   * @var string
   */
  public const SUBSCRIBE_BODY = 'Notifications on relevant updates at the current content.';

  /**
   * Default values for "Subscribed or unsubscribed" token.
   *
   * @var array
   */
  public const TOKENS = [
    'subscribe' => 'Subscribe to',
    'subscribed' => 'Subscribed to',
    'unsubscribed' => 'Unsubscribed from',
    'unsubscribe' => 'Unsubscribe from',
    're_subscribe' => 'Re-subscribe with push notifications for',
    'push' => 'push',
    'stop_pushing' => 'stop pushing',
    'clients' => [],
    'push_services' => self::PUSH_SERVICES,
  ];

  /**
   * A list of known/available push services endpoints.
   *
   * @var array
   */
  const PUSH_SERVICES = [
    'android.googleapis.com' => 'Android',
    'fcm.googleapis.com' => 'Chrome/Google',
    'updates.push.services.mozilla.com' => 'Firefox',
    'updates-autopush.stage.mozaws.net' => 'Mozilla',
    'updates-autopush.dev.mozaws.net' => 'Mozilla',
    '*.notify.windows.com' => 'Edge/Microsoft',
    '*.push.apple.com' => 'Safari/Apple',
  ];

  /**
   * Flood api data prefix.
   *
   * @var string
   */
  public const FLOOD_ID = 'pf_notifications.subscription.post';

  /**
   * Prepare libraries to attach.
   *
   * @param string $danse_key
   *   DANSE's unique action key.
   * @param int $danse_active
   *   An original value of danse subscription, before updating with our data.
   * @param \Drupal\Core\Entity\ContentEntityInterface<object>|null $entity
   *   Entity being a subscription subject. Typically, Node or Comment.
   *
   * @return array<string, string|array<string, string|int>>
   *   Data ready to be sent to front end via ajax commands.
   */
  public function prepareLibraries(string $danse_key, int $danse_active = 0, ContentEntityInterface $entity = NULL): array;

  /**
   * Create response to register subscription in front-end.
   *
   * @param int|array $op
   *   A current value for this danse entry in users_data.
   * @param string $danse_key
   *   DANSE's unique action key.
   * @param \Drupal\Core\Entity\ContentEntityInterface<object>|null $entity
   *   Entity being a subscription subject. Typically, Node or Comment.
   * @param null|\Drupal\Core\Ajax\AjaxResponse $response
   *   Any existing ajax response object that we might hook on.
   * @param int $danse_active
   *   An original value of danse subscription, before updating with our data.
   * @param bool $redirect
   *   When true response redirects/reloads based on default drupal behavior.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Modified ajax response which executed defined commands.
   */
  public function subscriptionResponse(int|array $op, string $danse_key, ContentEntityInterface $entity = NULL, AjaxResponse $response = NULL, int $danse_active = 0, bool $redirect = FALSE): AjaxResponse;

}
