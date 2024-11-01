<?php

namespace Drupal\danse_content\Plugin\Danse;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_content\Payload;
use Drupal\danse_content\SubscriptionOperation;
use Drupal\danse_content\Topic\Comment;
use Drupal\danse_content\Topic\Create;
use Drupal\danse_content\Topic\Delete;
use Drupal\danse_content\Topic\Publish;
use Drupal\danse_content\Topic\TopicInterface;
use Drupal\danse_content\Topic\Unpublish;
use Drupal\danse_content\Topic\Update;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "content",
 *   label = @Translation("Content"),
 *   description = @Translation("Manages all content entity types and their
 *   bundles.")
 * )
 */
class Content extends PluginBase {

  use ContentSettingsTrait;

  public const SETTING_CREATE_EVENT = 'create_event';

  /**
   * Entities that have been processed by the UI.
   *
   * @var array
   */
  static private array $processedEntitiesByUi = [];

  /**
   * Gets a list of supported topics.
   *
   * @return \Drupal\danse_content\Topic\TopicInterface[]
   *   The list of supported topics.
   */
  public static function topics(): array {
    static $topics;
    if ($topics === NULL) {
      $topics = [];
      $container = \Drupal::getContainer();
      if ($container !== NULL) {
        $topics = [
          Create::create($container),
          Update::create($container),
          Delete::create($container),
          Publish::create($container),
          Unpublish::create($container),
          Comment::create($container),
        ];
      }
    }
    return $topics;
  }

  /**
   * Gets the topic for the given ID.
   *
   * @param string $topic_id
   *   The topic ID.
   *
   * @return \Drupal\danse_content\Topic\TopicInterface
   *   The topic.
   */
  public static function topic(string $topic_id): TopicInterface {
    foreach (self::topics() as $topic) {
      if ($topic->id() === $topic_id) {
        return $topic;
      }
    }
    throw new \InvalidArgumentException('Invalid topic id');
  }

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * {@inheritdoc}
   */
  public function assertSubscriptionKey() {
    if (count($this->subscriptionKeyParts) === 4) {
      [$entityType, $bundle, $subscriptionMode, $topicId] = $this->subscriptionKeyParts;
      if ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE) {
        $subscriptionType = 'allow_subscription';
        $entity = TRUE;
      }
      else {
        if ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY) {
          $subscriptionType = 'allow_entity_subscription';
        }
        elseif ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_RELATED_ENTITY) {
          $subscriptionType = 'allow_related_entity_subscription';
        }
        else {
          return FALSE;
        }
        try {
          $entity = $this->entityTypeManager->getStorage($entityType)->load($bundle);
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          return FALSE;
        }
        if ($entity === NULL) {
          return FALSE;
        }
        $bundle = $entity->bundle();
      }

      $topic = self::topic($topicId);
      $config_id = $this->configId($entityType, $bundle);
      $config = $this->configFactory->get($config_id);
      if ($roles = $config->get($topic->getSettingKey($subscriptionType))) {
        $userRoles = $this->currentUser->getRoles();
        foreach ($roles as $role) {
          if (in_array($role, $userRoles, TRUE)) {
            return $entity;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Gets a list of bundles for which subscriptions are enabled.
   *
   * @return array
   *   List of bundles.
   */
  public function enabledSubscriptionBundles(): array {
    $cacheId = $this->buildCacheId('enabled_subscription_bundles');
    if ($cached = $this->cacheBackend->get($cacheId)) {
      return $cached->data;
    }
    $result = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
      $result[$entity_type] = [];
      foreach ($this->bundleInfo->getBundleInfo($entity_type) as $bundle => $bundleDef) {
        $config_id = $this->configId($definition->id(), $bundle);
        $config = $this->configFactory->get($config_id);
        foreach ([
          'allow_subscription',
          'allow_entity_subscription',
          'allow_related_entity_subscription',
        ] as $subscriptionType) {
          foreach (self::topics() as $topic) {
            if ($topic->assert($definition->id()) && $roles = $config->get($topic->getSettingKey($subscriptionType))) {
              $result[$entity_type][] = $bundle;
              break 2;
            }
          }
        }
      }
    }
    $this->cacheBackend->set($cacheId, $result, CacheBackendInterface::CACHE_PERMANENT, ['danse.cache:' . $this->pluginId]);
    return $result;
  }

  /**
   * Gets a list of entity types for which subscriptions are enabled.
   *
   * @return array
   *   List of entity types.
   */
  public function enabledSubscriptionEntityTypes(): array {
    $cacheId = $this->buildCacheId('enabled_subscription_entity_types');
    if ($cached = $this->cacheBackend->get($cacheId)) {
      return $cached->data;
    }
    $result = [];
    foreach ($this->enabledSubscriptionBundles() as $entity_type => $enabledSubscriptionBundle) {
      if (!empty($enabledSubscriptionBundle)) {
        $result[] = $entity_type;
      }
    }
    $this->cacheBackend->set($cacheId, $result, CacheBackendInterface::CACHE_PERMANENT, ['danse.cache:' . $this->pluginId]);
    return $result;
  }

  /**
   * Get a list of available subscription operations for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $resetCache
   *   Set to TRUE, if the cache for this widget should be reset.
   *
   * @return \Drupal\danse_content\SubscriptionOperation[]
   *   The list of operations.
   */
  public function subscriptionOperations(ContentEntityInterface $entity, bool $resetCache = FALSE): array {
    $cacheId = $this->buildCacheId(implode(':', [
      'entity_subscription_operations',
      $entity->getEntityTypeId(),
      $entity->id(),
      $this->currentUser->id(),
    ]));
    if ($resetCache) {
      $this->cacheBackend->delete($cacheId);
    }
    elseif ($cached = $this->cacheBackend->get($cacheId)) {
      return $cached->data;
    }
    $operations = [];
    $config_id = $this->configId($entity->getEntityTypeId(), $entity->bundle());
    $config = $this->configFactory->get($config_id);
    $userRoles = $this->currentUser->getRoles();
    $userId = $this->currentUser->id();
    $subscriptionTypes = ['allow_subscription' => SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE];
    if ($entity->id()) {
      $subscriptionTypes['allow_entity_subscription'] = SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY;
      $subscriptionTypes['allow_related_entity_subscription'] = SubscriptionOperation::SUBSCRIPTION_MODE_RELATED_ENTITY;
    }
    foreach ($subscriptionTypes as $subscriptionType => $subscriptionMode) {
      foreach (self::topics() as $topic) {
        if ($topic->assert($entity->getEntityTypeId()) && !empty($config->get($topic->getSettingKey($subscriptionType)))) {
          foreach ($config->get($topic->getSettingKey($subscriptionType)) as $rid) {
            if (in_array($rid, $userRoles, TRUE)) {
              if ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE) {
                $key = $this->subscriptionKey($entity->getEntityTypeId(), $entity->bundle(), $subscriptionMode, $topic->id());
              }
              else {
                $key = $this->subscriptionKey($entity->getEntityTypeId(), $entity->id(), $subscriptionMode, $topic->id());
              }
              $operation = new SubscriptionOperation($entity, $key, $topic, $subscriptionMode);
              if ($this->userData->get('danse', $userId, $key)) {
                $operation->setUnsubscribeMode();
              }
              $operations[] = $operation;
              // Do not try for other roles as we already found one.
              break;
            }
          }
        }
      }
    }
    $this->cacheBackend->set($cacheId, $operations, CacheBackendInterface::CACHE_PERMANENT, ['danse.cache:' . $this->pluginId]);
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedSubscriptions($roles): array {
    $cacheId = $this->buildCacheId(implode(':', [
      'roles_subscriptions',
      implode('-', $roles),
    ]));
    if ($cached = $this->cacheBackend->get($cacheId)) {
      return $cached->data;
    }
    $subscriptions = [];
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $bundles = $this->bundleInfo->getBundleInfo($definition->id());
        if (empty($bundles)) {
          $bundles = [$definition->id() => []];
        }
        $numberOfBundles = count($bundles);
        foreach ($bundles as $bundle => $bundleInfo) {
          $config_id = $this->configId($definition->id(), $bundle);
          $config = $this->configFactory->get($config_id);
          foreach (self::topics() as $topic) {
            if ($topic->assert($definition->id())) {
              $relevantRoles = array_intersect(
                $config->get($topic->getSettingKey('allow_subscription')) ?? [],
                $roles
              );
              if (!empty($relevantRoles)) {
                $subscriptions[$this->subscriptionKey($definition->id(), $bundle, SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE, $topic->id())] =
                  empty($bundleInfo) || $numberOfBundles === 1 ?
                    $this->t('@topic @entity_type', [
                      '@entity_type' => strtolower($definition->getLabel()),
                      '@topic' => $topic->id(),
                    ]) :
                    $this->t('Subscribe to @bundle @entity_type (@topic)', [
                      '@bundle' => strtolower($bundleInfo['label']),
                      '@entity_type' => strtolower($definition->getLabel()),
                      '@topic' => $topic->id(),
                    ]);
              }
            }
          }
        }
      }
    }
    $this->cacheBackend->set($cacheId, $subscriptions, CacheBackendInterface::CACHE_PERMANENT, ['danse.cache:' . $this->pluginId]);
    return $subscriptions;
  }

  /**
   * Returns a list of topic available for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $force_create_topic
   *   Whether to always include the CREATE topic.
   *
   * @return \Drupal\danse_content\Topic\TopicInterface[]
   *   The list of topics.
   */
  public function topicsForEntity(ContentEntityInterface $entity, bool $force_create_topic = FALSE): array {
    $topics = ($force_create_topic || $entity->isNew()) ?
      [self::topic(TopicInterface::CREATE)] :
      [self::topic(TopicInterface::UPDATE)];
    if (!$force_create_topic && $this->isPublished($entity)) {
      $topics[] = self::topic(TopicInterface::UNPUBLISH);
    }
    else {
      $topics[] = self::topic(TopicInterface::PUBLISH);
    }
    return $topics;
  }

  /**
   * Gets a list of topics related to deleting an entity.
   *
   * @return \Drupal\danse_content\Topic\TopicInterface[]
   *   The list of topics.
   */
  public function topicsForEntityDeletion(): array {
    return [
      self::topic(TopicInterface::DELETE),
      self::topic(TopicInterface::UNPUBLISH),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array {
    $info = $form_state->getBuildInfo();
    if (!empty($info['callback_object']) && $info['callback_object'] instanceof ContentEntityFormInterface) {
      $callback = $info['callback_object'];
      /**
       * @var \Drupal\Core\Entity\ContentEntityInterface $entity
       */
      $entity = $callback->getEntity();
      if ($callback instanceof ContentEntityDeleteForm) {
        $topics = $this->topicsForEntityDeletion();
      }
      else {
        $topics = $this->topicsForEntity($entity);
      }
      if ($this->isKeyEnabled(self::SETTING_CREATE_EVENT, $topics, $entity->getEntityTypeId(), $entity->bundle())) {
        return $this->buildContentForm($form, $form_state, $entity, $topics);
      }
    }
    elseif ($route_name = $this->routeMatch->getRouteName()) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
        if (($definition instanceof ContentEntityTypeInterface) && $this->assertContentTypeSettingsForm($route_name, $definition)) {
          return $this->buildContentTypeSettingsForm($form, $form_state, $definition);
        }
      }
    }
    return [];
  }

  /**
   * Determines if the route name is for a content type settings form.
   *
   * @param string $route_name
   *   The route name.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $definition
   *   The content type definition.
   *
   * @return bool
   *   TRUE, if the given route name is the settings form of the given content
   *   type, FALSE otherwise.
   */
  private function assertContentTypeSettingsForm(string $route_name, ContentEntityTypeInterface $definition): bool {
    switch ($route_name) {
      case 'entity.taxonomy_vocabulary.overview_form':
        return FALSE;

      case 'entity.taxonomy_vocabulary.edit_form':
        return $definition->id() === 'taxonomy_term';

      default:
        if ($field_ui_base_route = $definition->get('field_ui_base_route')) {
          return $field_ui_base_route === $route_name;
        }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(EventInterface $event): Url {
    if ($payload = $event->getPayload()) {
      try {
        return $payload->getEntity()->toUrl();
      }
      catch (EntityMalformedException $e) {
        // @todo Log this exception.
      }
    }
    return parent::getRedirectUrl($event);
  }

  /**
   * Determines if the given entity is published.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return bool
   *   TRUE, if the content entity is published, FALSE otherwise.
   */
  public function isPublished(ContentEntityInterface $entity): bool {
    if ($entity->isNew()) {
      return FALSE;
    }
    $entityType = $entity->getEntityType();
    if ($entityType->hasKey('status')) {
      $key = $entityType->getKey('status');
    }
    elseif ($entityType->hasKey('published')) {
      $key = $entityType->getKey('published');
    }
    else {
      return TRUE;
    }
    return (bool) $entity->get($key)->value;
  }

  /**
   * Determines if a key is enabled for a given content type.
   *
   * @param string $key
   *   The key to check.
   * @param \Drupal\danse_content\Topic\TopicInterface[] $topics
   *   The list of topics.
   * @param string $entity_type_id
   *   The ID of the content type.
   * @param string|null $bundle
   *   Optional, the bundle ID.
   *
   * @return bool
   *   TRUE, if the key is enabled, FALSE otherwise.
   */
  protected function isKeyEnabled(string $key, array $topics, string $entity_type_id, string $bundle = NULL): bool {
    $config = $this->configFactory->get($this->configId($entity_type_id, $bundle));
    foreach ($topics as $topic) {
      if ($config->get($topic->getSettingKey($key))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Adds the DANSE settings to an entity type settings form.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The settings form state.
   * @param \Drupal\Core\Entity\EntityTypeInterface $definition
   *   The definition of the entity type.
   *
   * @return array
   *   The submit handler for this plugin.
   */
  protected function buildContentTypeSettingsForm(array &$form, FormStateInterface $form_state, EntityTypeInterface $definition): array {
    if (($bundleKey = $definition->getBundleEntityType()) && $parameter = $this->routeMatch->getParameter($bundleKey)) {
      $bundle = $parameter->id();
    }
    else {
      $bundle = NULL;
    }
    $config_id = $this->configId($definition->id(), $bundle);
    $config = $this->configFactory->get($config_id);
    $form['danse_config_id'] = ['#type' => 'hidden', '#value' => $config_id];
    $roles = $this->query->rolesAsSelectList();

    $header = [''];
    foreach (self::topics() as $topic) {
      if ($topic->assert($definition->id())) {
        $header[] = $topic->settingsFormLabel();
      }
    }

    $form['danse'] = [
      '#type' => 'details',
      '#group' => isset($form['additional_settings']) ? 'additional_settings' : 'advanced',
      '#title' => $this->t('DANSE'),
      '#tree' => TRUE,
      '#weight' => 11,
    ];
    $form['danse']['events'] = [
      '#type' => 'table',
      '#header' => $header,
    ];
    foreach ($this->settings() as $setting => $settingDef) {
      $form['danse']['events'][$setting]['label'] = [
        '#markup' => $settingDef['label'],
      ];
      $states = [];
      foreach (self::topics() as $topic) {
        $states[':input[name="danse[events][' . $setting . '][' . $topic->id() . ']"]'] = ['checked' => FALSE];
      }
      foreach ($this->defaultSettingLabels() + $this->defaultSubscriptionLabels() as $key => $label) {
        $form['danse']['events'][$key]['label'] = [
          '#type' => 'container',
          '#states' => [
            'invisible' => $states,
          ],
          'markup' => [
            '#markup' => $label,
          ],
        ];
      }
      $subscriptionLabels = $this->defaultSubscriptionLabels();
      foreach (self::topics() as $topic) {
        if ($topic->assert($definition->id())) {
          $topicId = $topic->id();
          $form['danse']['events'][$setting][$topicId] = [
            '#type' => 'checkbox',
            '#default_value' => $config->get($topic->getSettingKey($setting)),
          ];
          foreach ($this->defaultSettings($roles, $config, $topic, FALSE) + $this->defaultSubscriptions($roles, $config, $topic, FALSE) as $key => $element) {
            if (isset($subscriptionLabels[$key]) || $topic->accessToDefaultSettings()) {
              $form['danse']['events'][$key][$topicId] = $element;
              unset($form['danse']['events'][$key][$topicId]['#description']);
              $form['danse']['events'][$key][$topicId]['#states'] = [
                'visible' => [
                  ':input[name="danse[events][' . $setting . '][' . $topicId . ']"]' => ['checked' => TRUE],
                ],
              ];
            }
          }
        }
      }
    }

    return [$this, 'submitContentTypeSettingsForm'];
  }

  /**
   * Submit callback for the entity type settings form.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The settings form state.
   */
  public function submitContentTypeSettingsForm(array &$form, FormStateInterface $form_state): void {
    $config_id = $form_state->getValue(['danse_config_id']);
    $config = $this->configFactory->getEditable($config_id);
    $config
      ->set('events', $form_state->getValue(['danse', 'events']))
      ->save();
    $this->cacheTagsInvalidator->invalidateTags(['danse.cache:' . $this->pluginId]);
  }

  /**
   * Adds the DANSE settings to an entity form.
   *
   * @param array $form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The entity form state.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\danse_content\Topic\TopicInterface[] $topics
   *   The topics.
   *
   * @return array
   *   The form with added DANSE settings.
   */
  protected function buildContentForm(array &$form, FormStateInterface $form_state, ContentEntityInterface $entity, array $topics): array {
    $config_id = $this->configId($entity->getEntityTypeId(), $entity->bundle());
    $config = $this->configFactory->get($config_id);
    $topicIds = [];
    foreach ($topics as $topic) {
      $topicIds[] = $topic->id();
    }
    $form['danse_topics'] = [
      '#type' => 'hidden',
      '#value' => implode(',', $topicIds),
    ];
    $form['danse_was_published'] = [
      '#type' => 'hidden',
      '#value' => $this->isPublished($entity),
    ];

    $accessSettings = FALSE;
    $userRoles = $this->currentUser->getRoles();
    foreach ($topics as $topic) {
      $relevantRoles = array_intersect(
        $config->get($topic->getSettingKey('access settings')) ?? [],
        $userRoles
      );
      if (!empty($relevantRoles)) {
        $accessSettings = TRUE;
        break;
      }
    }

    $form['danse'] = [
      '#type' => 'details',
      '#group' => isset($form['additional_settings']) ? 'additional_settings' : 'advanced',
      '#title' => $this->t('DANSE'),
      '#weight' => 11,
      '#access' => $accessSettings,
      '#tree' => TRUE,
    ];
    $form['danse'] += $this->defaultSettings([], $config, $topics[0], TRUE);

    // Add an entity builder to the form which marks the edited entity object as
    // processed by UI. This is needed so entity editing hooks know in advance
    // that the DANSE settings will be saved later by submit handler.
    $form['#entity_builders'][] = [$this, 'setEntityProcessByUi'];

    return [$this, 'submitContentForm'];
  }

  /**
   * Entity builder that marks entities as processed by UI.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function setEntityProcessByUi($entity_type_id, ContentEntityInterface $entity, array &$form, FormStateInterface &$form_state): void {
    self::$processedEntitiesByUi[$entity_type_id][$entity->id() ?? 0] = TRUE;
  }

  /**
   * Submit callback for the entity form.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The settings form state.
   */
  public function submitContentForm(array &$form, FormStateInterface $form_state): void {
    $info = $form_state->getBuildInfo();
    /**
     * @var \Drupal\Core\Entity\ContentEntityFormInterface $callback
     */
    $callback = $info['callback_object'];
    /**
     * @var \Drupal\Core\Entity\ContentEntityInterface $entity
     */
    $entity = $callback->getEntity();
    $topics = [];
    foreach (explode(',', $form_state->getValue('danse_topics')) as $topicId) {
      $topics[] = self::topic($topicId);
    }
    $orgStatus = (bool) $form_state->getValue('danse_was_published');
    $values = $form_state->getValue('danse');
    $this->processEntity($entity, $topics, $orgStatus, $values);
  }

  /**
   * Helper function to determine is the entity was processed through the UI.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $isNew
   *   Whether the entity is new.
   *
   * @return bool
   *   TRUE if the entity got processed through the UI, FALSE otherwise.
   */
  protected function entityProcessByUi(ContentEntityInterface $entity, bool $isNew): bool {
    if (isset(self::$processedEntitiesByUi[$entity->getEntityTypeId()][$entity->id()])) {
      return TRUE;
    }
    if ($isNew && isset(self::$processedEntitiesByUi[$entity->getEntityTypeId()][0])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Process topics on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\danse_content\Topic\TopicInterface[] $topics
   *   The topics to process.
   * @param bool $orgStatus
   *   The original entity status.
   * @param array|null $values
   *   An optional list of values.
   * @param bool $isNew
   *   Whether the entity is new.
   */
  public function processEntity(ContentEntityInterface $entity, array $topics, bool $orgStatus, array $values = NULL, bool $isNew = FALSE): void {
    static $processedEntities = [];
    if ($values === NULL && $this->entityProcessByUi($entity, $isNew)) {
      // We got here from an entity hook, but don't want to process that entity
      // right here, because it got edited through the UI and we want to grab
      // the settings from the entity edit form. So let's skip this, we will
      // be getting here a second time for this entity and process it then.
      return;
    }
    if (!empty($processedEntities[$entity->getEntityTypeId()][$entity->id()])) {
      // Avoid double processing.
      return;
    }
    $processedEntities[$entity->getEntityTypeId()][$entity->id()] = TRUE;
    if ($values === NULL) {
      if (!$this->isKeyEnabled(self::SETTING_CREATE_EVENT, $topics, $entity->getEntityTypeId(), $entity->bundle())) {
        return;
      }
      $config_id = $this->configId($entity->getEntityTypeId(), $entity->bundle());
      $config = $this->configFactory->get($config_id);
      $values = [];
      foreach ($this->defaultSettings([], $config, $topics[0], FALSE) as $key => $value) {
        $values[$key] = $value['#default_value'];
      }
    }
    switch ($topics[0]->id()) {
      case TopicInterface::CREATE:
        $create = $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::CREATE)], $entity->getEntityTypeId(), $entity->bundle());
        $publish = $this->isPublished($entity) && $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::PUBLISH)], $entity->getEntityTypeId(), $entity->bundle());
        $this->createContent($entity, $values['push'], $values['force'], $values['silent'], $create, $publish);
        break;

      case TopicInterface::UPDATE:
        $update = $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::UPDATE)], $entity->getEntityTypeId(), $entity->bundle());
        $newStatus = $this->isPublished($entity);
        $publish = FALSE;
        $unpublish = FALSE;
        if ($newStatus !== $orgStatus) {
          if ($newStatus) {
            $publish = $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::PUBLISH)], $entity->getEntityTypeId(), $entity->bundle());
          }
          else {
            $unpublish = $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::UNPUBLISH)], $entity->getEntityTypeId(), $entity->bundle());
          }
        }
        $this->updateContent($entity, $values['push'], $values['force'], $values['silent'], $update, $publish, $unpublish);
        break;

      case TopicInterface::DELETE:
        $delete = $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::DELETE)], $entity->getEntityTypeId(), $entity->bundle());
        $unpublish = $this->isPublished($entity) && $this->isKeyEnabled(self::SETTING_CREATE_EVENT, [self::topic(TopicInterface::UNPUBLISH)], $entity->getEntityTypeId(), $entity->bundle());
        $this->deleteContent($entity, $values['push'], $values['force'], $values['silent'], $delete, $unpublish);
        break;
    }
  }

  /**
   * Process the CREATE topic on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $push
   *   Whether the topic should be pushed.
   * @param bool $force
   *   Whether the push should be forced.
   * @param bool $silent
   *   Whether this operation should be silenced.
   * @param bool $create
   *   Whether a create event should be created.
   * @param bool $publish
   *   Whether the publish content topic should be processed.
   */
  protected function createContent(ContentEntityInterface $entity, bool $push, bool $force, bool $silent, bool $create, bool $publish): void {
    if ($create) {
      // Make create event silent if we also publish the entity afterwards.
      $createSilent = $publish ? TRUE : $silent;
      $this->createEvent(TopicInterface::CREATE, $entity->label() ?? 'unknown', new Payload($entity), $push, $force, $createSilent);
    }
    if ($publish) {
      $this->publishContent($entity, $push, $force, $silent);
    }
  }

  /**
   * Process the UPDATE topic on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $push
   *   Whether the topic should be pushed.
   * @param bool $force
   *   Whether the push should be forced.
   * @param bool $silent
   *   Whether this operation should be silenced.
   * @param bool $update
   *   Whether an update event should be created.
   * @param bool $publish
   *   Whether the publish content topic should be processed.
   * @param bool $unpublish
   *   Whether the unpublish content topic should be processed.
   */
  protected function updateContent(ContentEntityInterface $entity, bool $push, bool $force, bool $silent, bool $update, bool $publish, bool $unpublish): void {
    if ($update) {
      $this->createEvent(TopicInterface::UPDATE, $entity->label() ?? 'unknown', new Payload($entity), $push, $force, $silent);
      // Make sure that a potential second event won't create duplicate
      // notifications.
      $silent = TRUE;
    }
    if ($publish) {
      $this->publishContent($entity, $push, $force, $silent);
    }
    if ($unpublish) {
      $this->unpublishContent($entity, $push, $force, $silent);
    }
  }

  /**
   * Process the DELETE topic on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $push
   *   Whether the topic should be pushed.
   * @param bool $force
   *   Whether the push should be forced.
   * @param bool $silent
   *   Whether this operation should be silenced.
   * @param bool $delete
   *   Whether a delete event should be created.
   * @param bool $unpublish
   *   Whether the unpublish content topic should be processed.
   */
  protected function deleteContent(ContentEntityInterface $entity, bool $push, bool $force, bool $silent, bool $delete, bool $unpublish): void {
    if ($unpublish) {
      $this->unpublishContent($entity, $push, $force, $silent);
      // Make sure that a potential second event won't create duplicate
      // notifications.
      $silent = TRUE;
    }
    if ($delete) {
      $this->createEvent(TopicInterface::DELETE, $entity->label() ?? 'unknown', new Payload($entity), $push, $force, $silent);
    }
  }

  /**
   * Process the PUBLISH topic on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $push
   *   Whether the topic should be pushed.
   * @param bool $force
   *   Whether the push should be forced.
   * @param bool $silent
   *   Whether this operation should be silenced.
   */
  protected function publishContent(ContentEntityInterface $entity, bool $push, bool $force, bool $silent): void {
    $this->createEvent(TopicInterface::PUBLISH, $entity->label() ?? 'unknown', new Payload($entity), $push, $force, $silent);
  }

  /**
   * Process the UNPUBLISH topic on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $push
   *   Whether the topic should be pushed.
   * @param bool $force
   *   Whether the push should be forced.
   * @param bool $silent
   *   Whether this operation should be silenced.
   */
  protected function unpublishContent(ContentEntityInterface $entity, bool $push, bool $force, bool $silent): void {
    $this->createEvent(TopicInterface::UNPUBLISH, $entity->label() ?? 'unknown', new Payload($entity), $push, $force, $silent);
  }

}
