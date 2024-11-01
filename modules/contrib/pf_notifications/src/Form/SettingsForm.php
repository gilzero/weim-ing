<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\pf_notifications\Service\KeysManagerInterface;
use Drupal\pf_notifications\Service\PushInterface;
use Drupal\pf_notifications\Service\SubscriptionInterface;
use Minishlink\WebPush\VAPID;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Push framework notifications settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Theme handler service.
   *
   * @var \Drupal\pf_notifications\Service\KeysManagerInterface
   */
  protected KeysManagerInterface $keysManager;

  /**
   * Theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * File Url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Flood service.
   *
   * @var \Drupal\Core\Flood\DatabaseBackend
   */
  protected DatabaseBackend $flood;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Push notifications push service.
   *
   * @var \Drupal\pf_notifications\Service\BaseInterface
   */
  protected BaseInterface $service;

  /**
   * Push notifications subscription service.
   *
   * @var \Drupal\pf_notifications\Service\SubscriptionInterface
   */
  protected SubscriptionInterface $subscription;

  /**
   * Push notifications push service.
   *
   * @var \Drupal\pf_notifications\Service\PushInterface
   */
  protected PushInterface $push;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->keysManager = $container->get('pf_notifications.keys_manager');
    $instance->themeHandler = $container->get('theme_handler');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->flood = $container->get('flood');
    $instance->formBuilder = $container->get('form_builder');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->service = $container->get('pf_notifications.base');
    $instance->subscription = $container->get('pf_notifications.subscription');
    $instance->push = $container->get('pf_notifications.push');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'pf_notifications_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  protected function getEditableConfigNames(): array {
    return ['pf_notifications.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'pf_notifications/subscribe';

    $config = $this->config('pf_notifications.settings');
    $keys = $this->service->getKeys();
    if (empty($keys)) {
      $keys['public_key'] = $form_state->getValue(['vapid', 'public_key']);
      $keys['private_key'] = $form_state->getValue(['vapid', 'private_key']);
    }

    if (!$this->checkPermissions()) {
      $permissions_link = Link::createFromRoute($this->t('Access POST on Push notification subscription resource'), 'user.admin_permissions', [], [
        'attributes' => ['target' => '_blank'],
        'fragment' => 'module-rest',
      ])->toString();
      $this->messenger()->addWarning($this->t('@link is not set for any other role than <em>Administrator</em> role.', [
        '@link' => $permissions_link,
      ]));
    }

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Other administration pages'),
      '#open' => TRUE,
    ];

    $form['info']['reports'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage Subscriptions'),
      '#url' => Url::fromRoute(BaseInterface::REDIRECT_ROUTE),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];

    $form['channel'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Push framework channel plugin'),
      '#description' => $this->t('Push framework channel plugin settings.'),
    ];
    $form['channel']['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('Disable if you want to stop sending push notifications while keeping settings below.'),
      '#default_value' => $config->get('active'),
    ];
    $form['channel']['danse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('DANSE data'),
      '#description' => $this->t('Disable this to exclude storing web push subscription data in <code>users_data</code> table as a <code>value</code> column of module:danse name:content-[...].'),
      '#default_value' => $config->get('danse'),
      // Temp.
      '#disabled' => TRUE,
    ];

    $has_keys = $keys['public_key'] && $keys['private_key'];

    $form['vapid'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Authentication (VAPID)'),
      '#prefix' => '<div id="pf-notifications-vapid-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('<p>Recommended unless you want to use automatic authentication. <a target="_blank" href="@auth_info_link">More details</a>.</p><p>Once the keys are generated they are disabled and shall not be changed unless needed. Once you save keys there will be <strong><em>Unlock</em> button to clear and optionally generate a new ones after. Note that any change here triggers deletion of all subscriptions for all users</strong>. TODO: Put that in the batch.</p>', [
        '@auth_info_link' => 'https://github.com/web-push-libs/web-push-php#authentication-vapid',
      ]),
    ];
    $form['vapid']['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#description' => $this->t('Uncompressed public key P-256 encoded in Base64-URL'),
      '#default_value' => $keys['public_key'] ?? NULL,
      '#disabled' => $keys['public_key'] ?? FALSE,
      '#process' => ['::processKey'],
    ];
    // @todo Make this (somehow) be a password field?
    // Tried some of these options:
    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/password#allowing_autocomplete
    // but browser still "manages" the password offering the saved ones.
    $form['vapid']['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#description' => $this->t('The secret multiplier of the private key encoded in Base64-URL'),
      '#default_value' => $keys['private_key'] ?? NULL,
      '#disabled' => $keys['private_key'] ?? FALSE,
      '#process' => ['::processKey'],
    ];
    $form['vapid']['actions'] = [
      '#type' => 'actions',
    ];
    $form['vapid']['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate and Save'),
      '#submit' => ['::saveKeys'],
      '#disabled' => $has_keys,
    ];

    if ($has_keys) {
      $form['vapid']['actions']['clear'] = [
        '#type' => 'link',
        '#title' => $this->t('Unlock'),
        '#url' => Url::fromRoute('pf_notifications.reset_keys', ['op' => 'clear']),
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button button--danger'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 700]),
        ],
      ];
    }

    $form['options'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('WebPush Options'),
    ];
    $form['options']['ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Time To Live'),
      '#description' => $this->t("Specifying the number of seconds you want your push message to live on the push service before it's delivered."),
      '#default_value' => $config->get('ttl'),
    ];
    $form['options']['urgency'] = [
      '#type' => 'select',
      '#title' => $this->t('Urgency'),
      '#description' => $this->t(
        "Urgency indicates to the push service how important a message is to the user.
        This can be used by the push service to help conserve the battery life of a user's
        device by only waking up for important messages when battery is low."
      ),
      '#options' => [
        'very-low' => $this->t('Very low'),
        'low' => $this->t('Low'),
        'normal' => $this->t('Normal'),
        'high' => $this->t('High'),
      ],
      '#default_value' => $config->get('urgency'),
    ];
    $form['options']['topic'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Topic'),
      '#description' => $this->t(
        'Either use <code>[danse_notification:topic]</code> token or a plain string which must contain only alphanumeric characters.
        Topics are strings that can be used to replace a pending messages with a new message
        if they have matching topic names. This is useful in scenarios where multiple messages
        are sent while a device is offline, and you really only want a user to see the latest
        message when the device is turned on.'
      ),
      '#maxlength' => 32,
      // '#pattern' => '[a-zA-Z0-9]+',
      '#default_value' => $config->get('topic'),
    ];
    $form['options']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t(
        'If you send tens of thousands notifications at a time, you may get memory overflows
        due to how endpoints are called in Guzzle. In order to fix this, WebPush sends
        notifications in batches.'
      ),
      '#default_value' => $config->get('batch_size'),
    ];
    $form['options']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('This will add success/fail logs upon push. Recommended to turn off on production.'),
      '#default_value' => $config->get('debug'),
    ];

    $form['subscription'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Subscription notification content'),
      '#description' => $this->t('Define properties/content for a notification that pops when user subscribe and unsubscribe from the content. Note that push notifications utilize default <a href="/admin/config/system/push_framework">Content pattern</a>. Additionally it can be altered with <code>hook_pf_notifications_push_data</code> along with push options, see <code>hook_pf_notifications.api.php</code>.'),
      '#states' => [
        'open' => [
          [
            ':input[name="subscription[skip]"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ];
    $form['subscription']['skip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip subscription welcome message'),
      '#description' => $this->t('Do not trigger browser notification when (un) subscribing.'),
      '#default_value' => $config->get('skip'),
    ];
    $form['subscription']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification title'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('title'),
      '#states' => [
        'invisible' => [
          [
            ':input[name="subscription[skip]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['subscription']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification content'),
      '#required' => TRUE,
      '#default_value' => $config->get('body'),
      '#states' => [
        'invisible' => [
          [
            ':input[name="subscription[skip]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $theme_path = $this->themeHandler->getTheme($this->themeHandler->getDefault())->getPath();
    $default_icon = $this->fileUrlGenerator->generateAbsoluteString($theme_path . '/favicon.ico');
    $form['subscription']['icon'] = [
      '#type' => 'url',
      '#title' => $this->t('Notification icon'),
      '#description' => $this->t('Enter absolute url of the icon which will show in the notification. E.g @default_icon', [
        '@default_icon' => $default_icon,
      ]),
      '#maxlength' => 512,
      '#size' => 60,
      '#default_value' => $this->defaultValue($form_state, 'icon') ?? $default_icon,
      '#states' => [
        'invisible' => [
          [
            ':input[name="subscription[skip]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $default_url_route = [
      'route' => '<front>',
      'params' => [],
    ];
    $form['subscription']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Notification link url'),
      '#description' => $this->t('Enter the URL on which user will be redirected after clicking on the notification.'),
      '#maxlength' => 2048,
      '#size' => 64,
      '#default_value' => $this->defaultValue($form_state, 'url', $default_url_route),
      '#states' => [
        'invisible' => [
          [
            ':input[name="subscription[skip]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $form['subscription']['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'entity' => 'user',
        'push-object' => 'push-object',
      ],
      '#global_types' => TRUE,
    ];

    $form['flood'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Flood control for subscriptions'),
      '#description' => $this->t('This functionality is still BETA, not yet properly tested. Feedback welcome!'),
    ];
    $form['flood']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable flood control'),
      '#default_value' => $config->get('enable'),
    ];
    $form['flood']['threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold'),
      '#description' => $this->t('The maximum number of times each user can do this event per time window.'),
      '#default_value' => $config->get('threshold'),
      '#required' => TRUE,
    ];
    $form['flood']['window'] = [
      '#type' => 'number',
      '#title' => $this->t('Window'),
      '#description' => $this->t('Number of seconds in the time window for this event'),
      '#default_value' => $config->get('window'),
      '#required' => TRUE,
    ];
    $form['flood']['clear'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Clear some temporary blocks.'),
      '#description' => $this->t('This will remove <code>pf_subscriptions</code> entry from <em>flood</em> table.<br />If IP address is not entered below - ALL <code>pf_subscriptions</code> entries will be cleared.'),
      '#prefix' => '<div id="pf-notifications-flood-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['flood']['clear']['ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address to clear'),
      '#default_value' => $config->get('ip'),
      '#size' => 28,
    ];
    $form['flood']['clear']['actions'] = [
      '#type' => 'actions',
      'clear' => [
        '#type' => 'submit',
        '#value' => $this->t('Clear'),
        '#submit' => ['::clearFlood'],
        '#ajax' => [
          '#callback' => '::ajaxKey',
          '#wrapper' => 'pf-notifications-flood-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Clearing blocked pf subscriptions...'),
          ],
        ],
      ],
    ];

    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test notification'),
      '#description' => $this->t('In order to test notification you need to subscribe in front-end first.'),
      '#tree' => TRUE,
      '#prefix' => '<div id="pf-notifications-test-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['test']['status'] = [
      '#type' => 'status_messages',
    ];

    $form['test']['subscribe'] = [
      '#type' => 'checkbox',
      '#weight' => 20,
      '#title' => $this->t('Subscribe to test'),
      '#description' => $this->t('VAPID keys must be generated for this to work.'),
      '#default_value' => $config->get('subscribe'),
      '#disabled' => !$has_keys,
      '#ajax' => [
        'callback' => '::ajaxSubscribe',
        'wrapper' => 'pf-notifications-test-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Subscribing for test notification.'),
        ],
      ],
    ];

    $form['test']['actions'] = [
      '#type' => 'actions',
    ];
    $form['test']['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#submit' => ['::testNotificationSubmit'],
      '#validate' => ['::testNotificationValidate'],
      '#ajax' => [
        'callback' => '::ajaxKeys',
        'wrapper' => 'pf-notifications-test-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Sending test notification.'),
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Match alphanumeric characters strictly.
    if ($form_state->getValue(['options', 'topic']) != PushInterface::DANSE_TOKENS['topic']) {
      $match = preg_match('/^[\p{L}\p{N}]+$/u', $form_state->getValue(['options', 'topic']));
      if (!$match) {
        $form_state->setErrorByName('options][topic', $this->t('Only alphanumeric characters, or exclusive <em>[danse_notification:topic]</em> token are allowed for a Topic'));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $existing_keys = $this->service->getKeys();
    $existing_public_key = $existing_keys['public_key'] ?? NULL;
    $existing_private_key = $existing_keys['private_key'] ?? NULL;

    $info = FALSE;
    if ($form_state->getValue(['vapid', 'public_key']) != $existing_public_key) {
      $this->keysManager->setKey('vapid_public', $form_state->getValue(['vapid', 'public_key']));
      $info = TRUE;
    }
    if ($form_state->getValue(['vapid', 'private_key']) != $existing_private_key) {
      $this->keysManager->setKey('vapid_private', $form_state->getValue(['vapid', 'private_key']));
      $info = TRUE;
    }

    if ($info) {
      $this->info();
    }

    $config = $this->config('pf_notifications.settings');
    $containers = [
      'channel',
      'options',
      'subscription',
      'flood',
      'test',
    ];

    foreach ($containers as $container) {
      $skip = [
        'actions',
        'clear',
      ];
      foreach (Element::children($form[$container]) as $property) {
        if (!in_array($property, $skip)) {
          $config->set($property, $form_state->getValue([$container, $property]));
        }
      }
    }

    // Save config data.
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Generate and save the new VAPID keys.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   */
  public function saveKeys(array &$form, FormStateInterface $form_state): void {

    try {

      $keys = VAPID::createVapidKeys();

      // Check if we got new valid keys.
      if (empty($keys)) {
        $this->info('error');
      }

      $existing_keys = $this->service->getKeys();
      $existing_public_key = $existing_keys['public_key'] ?? NULL;
      $existing_private_key = $existing_keys['private_key'] ?? NULL;
      $new_public_key = NULL;
      $new_private_key = NULL;

      // Save the new keys in "ph_notifications" table.
      if (isset($keys['publicKey']) && $keys['publicKey'] != $existing_public_key) {
        $new_public_key = $this->keysManager->setKey('vapid_public', $keys['publicKey']);
      }
      if (isset($keys['privateKey']) && $keys['privateKey'] != $existing_private_key) {
        $new_private_key = $this->keysManager->setKey('vapid_private', $keys['privateKey']);
      }

      if ($new_public_key && $new_private_key) {
        $this->service->getUserData()->delete('pf_notifications', $this->currentUser()->id(), 'test');
        $this->messenger()->addStatus($this->t('Public and private keys have been generated and saved.'));
      }
    }
    catch (\ErrorException $e) {
      $this->messenger()->addError($e->getMessage());
      $this->info('error', $e->getMessage());
    }

    $this->service->invalidateCacheTags();
    $this->service->invalidateCacheTags('danse');
    $form_state->setRedirect('pf_notifications.settings');
  }

  /**
   * Clears entry, or entries from flood table.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   */
  public function clearFlood(array &$form, FormStateInterface $form_state): void {
    if ($ip = $form_state->getValue(['flood', 'clear', 'ip'])) {
      $this->flood->clear(SubscriptionInterface::FLOOD_ID, $ip);
    }
    else {
      $this->flood->clearByPrefix(SubscriptionInterface::FLOOD_ID, 'pf_notifications');
    }
    $form_state->setRebuild();
  }

  /**
   * Subscribe checkbox - do front-end subscription for test notification.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function ajaxSubscribe(array &$form, FormStateInterface $form_state): AjaxResponse {
    $op = $form_state->getValue(['test', 'subscribe']);
    $uid = (int) $this->service->getCurrentUser()->id();
    $test_data = $this->service->getUserData()->get(BaseInterface::PROPERTY, $uid, BaseInterface::TEST_ID);
    if (is_array($test_data)) {
      $op = $test_data;
    }
    return $this->subscription->subscriptionResponse($op, BaseInterface::TEST_ID);
  }

  /**
   * Validate sending notification. User must be subscribed in front end.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   */
  public function testNotificationValidate(array &$form, FormStateInterface $form_state): void {
    $public_key = $form_state->getValue(['vapid', 'public_key']);
    $private_key = $form_state->getValue(['vapid', 'private_key']);
    if (!$public_key || !$private_key) {
      $error = $this->t('VAPID keys are not set.')->render();
      $form_state->setErrorByName('vapid][public_key', $error);
    }

    if (!$form_state->getValue(['test', 'subscribe'])) {
      $form['test']['#open'] = TRUE;
      $error = $this->t('You must subscribe to notifications first.')->render();
      $form_state->setError($form['test']['subscribe'], $error);
    }
  }

  /**
   * Send test notification.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   */
  public function testNotificationSubmit(array &$form, FormStateInterface $form_state): void {

    $url = $form_state->getValue(['subscription', 'url']);
    if (!$url) {
      $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    $options = $this->push->defaultOptions();
    $topic = $options['topic'] ?? NULL;

    if ($topic && $this->push->isToken($topic)) {
      $options['topic'] = PushInterface::DEFAULT_OPTIONS['topic'];
    }
    $push_data = [
      'content' => [
        'title' => Html::decodeEntities($this->t('Title default from DANSE')->render()),
        'body' => Html::decodeEntities($this->t('A body of a notification, by default DANSE provided')->render()),
        'icon' => $form_state->getValue(['subscription', 'icon']),
        'url' => $url,
      ],
      'options' => $options,
      'entity_data' => [
        'uid' => (int) $this->currentUser()->id(),
        'name' => $this->currentUser()->getDisplayName() ?: $this->currentUser()->getAccountName(),
      ],
      'test' => BaseInterface::TEST_ID,
    ];

    $this->push->sendNotification($push_data, TRUE);
    $form['test']['#open'] = TRUE;
  }

  /**
   * Ajax callback, just return any container details.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   *
   * @return array<string, string|array<string, mixed>>
   *   Elements' parent container.
   */
  public function ajaxKeys(array &$form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    $container = reset($trigger['#parents']);
    return $form[$container];
  }

  /**
   * Process callback to assign generated keys to form fields.
   *
   * @param array<string, string|array<string, mixed>> $element
   *   Referenced key form field/input.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   * @param array<string, string|array<string, mixed>> $complete_form
   *   This config form.
   *
   * @return array<string, string|array<string, mixed>>
   *   Key form field.
   */
  public function processKey(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $key = end($element['#parents']);
    $keys = $this->service->getKeys();
    $element['#value'] = $keys[$key] ?? '';
    return $element;
  }

  /**
   * Add some logs for operations.
   *
   * @param string $op
   *   The type of the log entry.
   * @param null|string $string
   *   Pre-defined message string.
   */
  protected function info(string $op = 'info', string $string = NULL): void {
    if ($op == 'info') {
      /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
      $current_user = $this->currentUser();
      $user_link = Link::createFromRoute($current_user->getDisplayName(), 'entity.user.canonical',
        ['user' => $current_user->id()],
        ['target' => '_blank'],
      );
      $message = $string ?: $this->t('@user has modified the Authentication keys', [
        '@user' => $user_link->toString(),
      ]);
      $this->logger('Push framework notifications')->info($message);
    }
    elseif ($op == 'error') {
      $message = $string ?: $this->t('An error occurred, the configuration was not modified. Please check the logs for details.');
      $this->logger('Push framework notifications')->error($message);
    }
  }

  /**
   * Check if any role other than admin role has reset permission assigned.
   *
   * @return bool
   *   TRUE if any role got assigned the rest permission.
   */
  protected function checkPermissions(): bool {
    $permissions = [];
    $roles = [];
    try {
      $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->messenger()->addError($e->getMessage());
    }
    foreach ($roles as $role) {
      /** @var \Drupal\user\Entity\Role $role */
      if (!$role->isAdmin()) {
        $permissions += $role->getPermissions();
      }
    }
    return in_array(BaseInterface::REST_PERMISSION, $permissions);
  }

  /**
   * Prepare default value for a field.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A current state (object) of this config form.
   * @param string $property
   *   This config form.
   * @param array<string, string|array<string>> $default_route
   *   Associative array with route name and route params array.
   *
   * @return null|string
   *   Found value string.
   */
  protected function defaultValue(FormStateInterface $form_state, string $property, array $default_route = []): string|NULL {
    $default_value = $form_state->getValue(['subscription', $property]) ?? $this->config('pf_notifications.settings')->get($property);
    if (!$default_value && isset($default_route['route']) && isset($default_route['params'])) {
      $default_value = Url::fromRoute($default_route['route'], $default_route['params'], ['absolute' => TRUE])->toString();
    }
    return $default_value;
  }

}
