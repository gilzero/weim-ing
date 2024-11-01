### About

Provides web push notifications for DANSE events.
Implements [Web Push library for PHP](https://github.com/web-push-libs/web-push-php), [DANSE](https://www.drupal.org/project/danse) and [Push framework](https://www.drupal.org/project/push_framework),
tailored and tested - a single case scenario though - to work with [PWA](https://www.drupal.org/project/pwa).

Many thanks to:
* [sebastix](https://www.drupal.org/u/sebastian-hagens) for creative contribution and management.
* [yfiervil](https://www.drupal.org/u/yfiervil) for the inspiration, ideas and code for [web_push](https://www.drupal.org/project/web_push) module.

For requirements see [composer.json](https://git.drupalcode.org/project/pf_notifications/-/blob/1.0.x/composer.json)

### Install and configure
1. Install module via composer, this will fetch all requirements.
2. Enable module

    `drush en pf_notifications`

3. Add this permission to Role(s) who are granted,
to subscribe to notifications:

    _Access POST on Push notification subscription resource_

4. Go to
_Configuration > System > Push framework > Notifications_
(/admin/config/system/push_framework/pf_notifications)
and generate or enter VAPID keys. Tweak any other settings there.

5. Try there Test notification too -
check "Subscribe" checkbox there before hitting "Send" button.
5. If not yet - configure DANSE content for Content and Comment types
you wish to implement notifications.
Check some resources [@sebastix.nl](https://sebastix.nl/blog/exploring-subscriptions-and-notifications-with-drupal-danse-module/) and perhaps this [issue](https://www.drupal.org/project/danse/issues/3194666).

### Usage scenario: a very basic example

1. First test on config page, test notification found there.
2. Login as `user1` and post a content that will hold the thread with comments.
Then, use DANSE's widget to subscribe for when other users comment there.
3. In the other browser login as `user2` and post a comment on the same content.
   Subscribe this user for any of available DANSE subscriptions
4. Run DANSE notifications and PF queues with drush


   `drush danse:notifications:create`

   `drush pf:sources:collect`

   `drush pf:queue:process`

Result: `user1` shall get the browser's/device's notification where is logged.

### Other usage
To use direct notifications, skipping DANSE and Push framework,
it's possible with a module's services.
Let's imagine custom usage, skipping DANSE's widget,
that is below with altering comment form by adding
a checkbox (for subscription) as well as ajax response
for front-end subscription.

In the same snippets, but commented out are also
example with some status like message for a user
who just submitted a form, without subscriptions or even usage of
some storage subscriptions other than danse's in users_table

In either case title and body of a notification are picked from
configuration form _/admin/config/system/push_framework/pf_notifications_.

    use Drupal\Core\Ajax\AjaxResponse;
    use Drupal\Core\Entity\ContentEntityInterface;
    use Drupal\Core\Form\FormStateInterface;
    use Drupal\danse_content\Topic\TopicInterface;
    use Drupal\pf_notifications\Service\BaseInterface;
    use Drupal\pf_notifications\Service\SubscriptionInterface;

    /**
     * Implements hook_form_FORM_ID_alter().
     */
    function MY_MODULE_form_comment_comment_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity = $form_state->getFormObject()->getEntity()) {
        /** @var \Drupal\pf_notifications\Service\BaseInterface $service */
        $service = \Drupal::service('pf_notifications.base');
        $uid = (int) $service->getCurrentUser()->id();
        $name = $service->getCurrentUser()->getDisplayName() ?: $service->getCurrentUser()->getAccountName();
        $entity_data = \Drupal::service('pf_notifications.base')->getEntityData($uid, $name, $entity);
        $parent_id = $entity_data['parent_id'] ?? NULL;
        $parent_type = $entity_data['parent_type'] ?? NULL;

        // Here we assign DANSE's "content-node-[nid]-1-comment" subscription.
        // Therefore, we assign entity to be node, a parent of this comment.
        if (!$parent_id || !$parent_type) {
          return;
        }

        // Define DANSE's subscription key by your choice.
        $danse_key = implode('-', [
          'module' => BaseInterface::DANSE_MODULE,
          'entity_type' => $parent_type,
          'entity_id' => $parent_id,
          // "Subscribe to this [node_bundle_label]
          // when it gets commented" action.
          'subscription_mode' => 1,
          'topic_id' => TopicInterface::COMMENT,
        ]);

        $form['danse_key'] = [
          '#type' => 'value',
          '#value' => $danse_key,
        ];

        $form['danse_subscribe'] = [
          '#type' => 'checkbox',
          '#title' => t('Subscribe to updates'),
          '#default_value' => FALSE,
          '#description' => t('Receive notifications when this thread gets updated.'),
          '#weight' => 30,
        ];

        // Add custom ajax and submit handler.
        $form['actions']['submit']['#ajax'] = [
          'callback' => 'MY_MODULE_notification',
          'progress' => [],
        ];
      }
      $attachments = \Drupal::service('pf_notifications.subscription')->prepareLibraries($danse_key, 0, $entity);
      if (isset($form['#attached'])) {
        $form['#attached'] += $attachments;
      }
      else {
        $form['#attached'] = $attachments;
      }
    }

    /**
      * Ajax callback on entity form submit.
      *
      * @param array $form
      *   Subscribing entity's form.
      * @param \Drupal\Core\Form\FormStateInterface $form_state
      *   Subscribing entity's form state object.
      *
      * @return \Drupal\Core\Ajax\AjaxResponse
      *   A response which creates subscription and notification in front-end.
      */
    function MY_MODULE_notification(array &$form, FormStateInterface $form_state): AjaxResponse {
      $response = new AjaxResponse();

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity = $form_state->getFormObject()->getEntity()) {

        // Subscribe or unsubscribe.
        $op = $form_state->getValue('danse_subscribe');
        $danse_key = $form_state->getValue('danse_key');

        // With push subscription and with storage
        // defined by DANSE in users_data table.
        $response = \Drupal::service('pf_notifications.subscription')->subscriptionResponse($op, $danse_key, $entity, $response, 0, TRUE);

        // With push subscription but with custom storage.
        // Subscriptions are stored elsewhere and/or generated on the fly.
        // $op = MY_MODULE_get_subscriptions($entity);
        // $response = \Drupal::service('pf_notifications.subscription')->subscriptionResponse($op, 'your-own-unique-id', $entity, $response, 0, TRUE);

        // If you want only notification in a role of status message like
        // and without push subscriptions.
        // $response = \Drupal::service('pf_notifications.subscription')->subscriptionResponse(SubscriptionInterface::NO_PUSH, 'your-own-unique-id', $entity, $response, 0);

        // @todo Implement notification source plugin other than DANSE's
        // and/or implement/document Message usage.
      }
      return $response;
    }

    function MY_MODULE_get_subscriptions(ContentEntityInterface $entity) {
      return [
        // 1st subscription
        'unique_id1' => [
        'uid' => (int) \Drupal::service('pf_notifications.base')->getCurrentUser()->id(),
        'danse_key' => 'your-own-unique-id',
        'danse_active' => $some_int ?? 0,
        'entity_uid' => $entity->get('uid')->target_id,
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'token' => 'unique_id1',
        'endpoint' => 'https://anyendpointurl.dev',
        'key' => 'subscription_encrypted_key',
        'subscribe' => '- irrelevant atm - ',
        'parent_id' => $some_value ?? NULL,
        'parent_entity_uid' => $some_value ?? NULL,
        'parent_type' => $some_value ?? NULL,
        'parent_comment' => $some_value ?? NULL,
        'parent_comment_uid' => $some_value ?? NULL,
      ],
      // 2nd subscription, the other browser/device/endpoint.
        'unique_id2' => [
          // ...
        ],
      ];
    }

### Hooks
See [pf_notifications.api.php](https://git.drupalcode.org/project/pf_notifications/-/blob/1.0.x/pf_notifications.api.php)
for a couple of hooks available.

### TODO

1. [x] Push notification shall link to DANSE message,
to flag it as seen?
2. [x] Implement a request for service worker de-registration/update.
If module is installed again soon user
may need to un-subscribe first in order to subscribe again.
3. [ ] Make flow fully operational without usage of DANSE content?
