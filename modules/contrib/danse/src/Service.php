<?php

namespace Drupal\danse;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Provides general services for the DANSE modules.
 */
class Service {

  use StringTranslationTrait;

  public const STATE_KEY_PAUSED = 'danse.event_tracking.paused';

  /**
   * The DANSE plugin manager.
   *
   * @var \Drupal\danse\PluginManager
   */
  protected PluginManager $pluginManager;

  /**
   * The user data storage service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The DANSE query service.
   *
   * @var \Drupal\danse\Query
   */
  protected Query $query;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The DANSE configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs the DANSE services.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\danse\PluginManager $plugin_manager
   *   The DANSE plugin manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data storage service.
   * @param \Drupal\danse\Query $query
   *   The DANSE query service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PluginManager $plugin_manager, UserDataInterface $user_data, Query $query, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, AccountProxyInterface $current_user) {
    $this->pluginManager = $plugin_manager;
    $this->userData = $user_data;
    $this->query = $query;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->currentUser = $current_user;

    $this->config = $configFactory->get('danse.settings');
  }

  /**
   * Checks access for the DANSE tabs in the user profile.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The owner of the visited profile.
   * @param bool $ignoreConfig
   *   Set to TRUE if the access check should ignore the configuration for the
   *   subscription tab, i.e. for checking access to the notifications tab.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(UserInterface $user = NULL, bool $ignoreConfig = FALSE): AccessResultInterface {
    if (
      $user && $user->isAuthenticated() &&
      ($ignoreConfig || $this->config->get('subscriptions_as_tab.events') || $this->config->get('subscriptions_as_tab.view')) &&
      $user->id() === $this->currentUser->id()
    ) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Checks access for the DANSE tabs in the user profile.
   *
   * @param int $user
   *   The ID of the owner of the visited profile.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessInt(int $user): AccessResultInterface {
    return $this->checkAccess(User::load($user), TRUE);
  }

  /**
   * Provides a list of instantiated DANSE plugins.
   *
   * @return \Drupal\danse\PluginInterface[]
   *   The list of instantiated plugins.
   */
  public function getPluginInstances(): array {
    $instances = [];
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      try {
        $instances[$id] = $this->pluginManager->createInstance($id);
      }
      catch (PluginException $e) {
        // We can safely ignore this here.
      }
    }
    return $instances;
  }

  /**
   * Provides an instantiated plugin.
   *
   * @param string $pluginId
   *   The plugin ID.
   *
   * @return \Drupal\danse\PluginInterface|null
   *   The instantiated plugin, NULL if the plugin ID is invalid.
   */
  public function getPluginInstance(string $pluginId): ?PluginInterface {
    $plugin = NULL;
    try {
      /**
       * @var \Drupal\danse\PluginBase $plugin
       */
      $plugin = $this->pluginManager->createInstance($pluginId);
    }
    catch (PluginException $e) {
      // @todo Log this exception.
    }
    return $plugin;
  }

  /**
   * Get the instantiated plugin for the given subscription key.
   *
   * @param string $key
   *   The subscription key.
   *
   * @return \Drupal\danse\PluginInterface|null
   *   The instantiated plugin, NULL if the subscription key is invalid.
   */
  public function getPluginInstanceFromSubscriptionKey(string $key): ?PluginInterface {
    $parts = explode('-', $key);
    try {
      $pluginId = array_shift($parts);
      /**
       * @var \Drupal\danse\PluginBase $plugin
       */
      $plugin = $this->pluginManager->createInstance($pluginId);
      $plugin->setSubscriptionKeyParts($parts);
      return $plugin;
    }
    catch (PluginException $e) {
      // @todo Log this exception.
    }
    return NULL;
  }

  /**
   * Adds a submit handler to the form.
   *
   * @param array $form
   *   The form.
   * @param array|string $callback
   *   The submit handler.
   */
  protected function addSubmitHandler(array &$form, $callback): void {
    $callback = is_string($callback) ? [$this, $callback] : $callback;
    if (isset($form['actions']['submit']['#submit'])) {
      foreach (array_keys($form['actions']) as $action) {
        if ($action !== 'preview'
          && isset($form['actions'][$action]['#type'])
          && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'][] = $callback;
        }
      }
    }
    else {
      $form['#submit'][] = $callback;
    }
  }

  /**
   * Build the configuration form for all plugins.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildForm(array &$form, FormStateInterface $form_state): void {
    $submitHandler = [];
    foreach ($this->getPluginInstances() as $plugin) {
      if ($result = $plugin->buildForm($form, $form_state)) {
        $submitHandler[] = $result;
      }
    }
    foreach ($submitHandler as $item) {
      $this->addSubmitHandler($form, $item);
    }
  }

  /**
   * Builds the form fragment for the user profile for subscriptions.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The form state.
   * @param bool $user_edit_mode
   *   TRUE, if the fragment will be embedded in the profile edit form, FALSE if
   *   it should be standalone.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildUserSubscriptionForm(array &$form, AccountInterface $account, bool $user_edit_mode = TRUE): void {
    $checkboxes = [];
    if ((bool) $this->config->get('subscriptions_as_tab.events') !== $user_edit_mode) {
      $roles = $account->getRoles();
      foreach ($this->getPluginInstances() as $plugin) {
        foreach ($plugin->getSupportedSubscriptions($roles) as $key => $title) {
          $checkboxes[$key] = [
            '#type' => 'checkbox',
            '#title' => $title,
            '#default_value' => $this->userData->get('danse', $account->id(), $key),
          ];
        }
      }
    }

    $views = [];
    if ((bool) $this->config->get('subscriptions_as_tab.view') !== $user_edit_mode) {
      foreach ($this->entityTypeManager->getStorage('view')->loadByProperties([
        'status' => 1,
        'tag' => 'DANSE Subscription',
      ]) as $view) {
        $views[] = [views_embed_view($view->id())];
      }
    }

    if (!empty($checkboxes) || !empty($views)) {
      if ($user_edit_mode) {
        $form['danse_subscriptions'] = [
          '#type' => 'details',
          '#title' => $this->t('Subscriptions'),
          '#open' => FALSE,
          '#tree' => TRUE,
          '#weight' => 9,
        ];
      }
      else {
        $form['danse_subscriptions'] = [
          '#type' => 'container',
          '#tree' => TRUE,
        ];
      }

      if (!empty($views)) {
        $form['danse_subscriptions']['view'] = $views;
      }

      if (!empty($checkboxes)) {
        if (empty($views)) {
          $form['danse_subscriptions']['settings'] = [
            '#type' => 'container',
            '#tree' => TRUE,
          ];
        }
        else {
          $form['danse_subscriptions']['settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Your subscriptions'),
            '#open' => (bool) $this->config->get('subscriptions_as_tab.expand'),
            '#tree' => TRUE,
          ];
        }
        $form['danse_subscriptions']['settings'] += $checkboxes;
        if (!$user_edit_mode) {
          $form['danse_subscriptions']['settings']['actions'] = ['#type' => 'actions'];
          $form['danse_subscriptions']['settings']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save subscriptions'),
            '#button_type' => 'primary',
          ];
        }
        $form['danse_account'] = ['#type' => 'value', '#value' => $account];
        $this->addSubmitHandler($form, 'submitUserSubscriptionForm');
      }
    }
  }

  /**
   * Submit callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitUserSubscriptionForm(array &$form, FormStateInterface $form_state): void {
    $this->saveUserSubscriptionForm($form_state, $form_state->getValue('danse_account'));
  }

  /**
   * Save user settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function saveUserSubscriptionForm(FormStateInterface $form_state, AccountInterface $account): void {
    foreach ($form_state->getValue(['danse_subscriptions', 'settings']) as $key => $value) {
      if ($key !== 'actions') {
        $this->userData->set('danse', $account->id(), $key, (int) $value);
      }
    }
  }

  /**
   * Create all outstanding notifications.
   */
  public function createNotifications(): void {
    foreach ($this->getPluginInstances() as $plugin) {
      $plugin->createNotifications();
    }
  }

  /**
   * Mark all notifications for a given payload as seen.
   *
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   */
  public function markSeen(PayloadInterface $payload): void {
    foreach ($this->query->findNotificationsForCurrentUser($payload) as $notification) {
      $notification->markSeen();
    }
  }

  /**
   * Mark all undelivered notifications for the user with ID $uid as delivered.
   *
   * @param int $uid
   *   The user ID.
   */
  public function markUserNotificationsDelivered(int $uid): void {
    try {
      /** @var \Drupal\danse\Entity\NotificationInterface[] $notifications */
      $notifications = $this->entityTypeManager->getStorage('')
        ->loadByProperties([
          'uid' => $uid,
          'delivered' => FALSE,
        ]);
      foreach ($notifications as $notification) {
        $notification->markDelivered();
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
  }

  /**
   * Delete all notifications for the user with ID $uid.
   *
   * @param int $uid
   *   The user ID.
   */
  public function deleteUserNotifications(int $uid): void {
    try {
      /** @var \Drupal\danse\Entity\NotificationInterface[] $notifications */
      $notifications = $this->entityTypeManager->getStorage('')
        ->loadByProperties([
          'uid' => $uid,
        ]);
      foreach ($notifications as $notification) {
        $notification->delete();
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      // @todo Log this exception.
    }
  }

  /**
   * Pause DANSE event tracking.
   */
  public function pause(): void {
    $this->state->set(self::STATE_KEY_PAUSED, TRUE);
  }

  /**
   * Resume DANSE event tracking.
   */
  public function resume(): void {
    $this->state->set(self::STATE_KEY_PAUSED, FALSE);
  }

  /**
   * Is the DANSE event tracking currently paused?
   *
   * @return bool
   *   Returns if the status is paused or not.
   */
  public function isPaused(): bool {
    return $this->state->get(self::STATE_KEY_PAUSED, FALSE);
  }

}
