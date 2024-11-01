<?php

namespace Drupal\danse_content\Plugin\Danse;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\danse_content\Topic\TopicInterface;

/**
 * Provides traits for content settings.
 *
 * @package Drupal\danse_content\Plugin\Danse
 */
trait ContentSettingsTrait {

  use StringTranslationTrait;

  /**
   * Get the config ID for an entity type and its optional bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   Optional, the bundle.
   *
   * @return string
   *   The config ID.
   */
  protected function configId(string $entity_type_id, string $bundle = NULL): string {
    return 'danse.settings.content.' . $entity_type_id . '.' . ($bundle ?? $entity_type_id);
  }

  /**
   * Gets the default settings.
   *
   * @return array
   *   The default settings.
   */
  protected function settings(): array {
    return [
      self::SETTING_CREATE_EVENT => [
        'label' => $this->t('Create event'),
      ],
    ];
  }

  /**
   * Gets the defaults setting labels.
   *
   * @return array
   *   The list of default setting labels.
   */
  protected function defaultSettingLabels(): array {
    return [
      'access settings' => $this->t('Access to settings'),
      'push' => $this->t('Default value for "Create push notification"'),
      'force' => $this->t('Default value for "Force push even if this creates duplicate notification"'),
      'silent' => $this->t('Default value for "Make this silent and do not create any notifications"'),
    ];
  }

  /**
   * Gets the default settings.
   *
   * @param array $roles
   *   List of user roles.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The DANSE config.
   * @param \Drupal\danse_content\Topic\TopicInterface $topic
   *   The topic.
   * @param bool $forEdit
   *   Whether the settings are for the edit form.
   *
   * @return array
   *   The form settings elements.
   */
  protected function defaultSettings(array $roles, ImmutableConfig $config, TopicInterface $topic, bool $forEdit): array {
    return [
      'access settings' => [
        '#type' => 'select',
        '#options' => $roles,
        '#multiple' => TRUE,
        '#default_value' => $config->get($topic->getSettingKey('access settings')) ?? [],
        '#title' => $this->t('Access to settings'),
        '#title_display' => 'hidden',
        '#access' => !$forEdit,
      ],
      'push' => [
        '#type' => 'checkbox',
        '#default_value' => $config->get($topic->getSettingKey('push')) ?? TRUE,
        '#title' => $this->t('Create push notification'),
        '#title_display' => $forEdit ? 'after' : 'hidden',
      ],
      'force' => [
        '#type' => 'checkbox',
        '#default_value' => $config->get($topic->getSettingKey('force')) ?? FALSE,
        '#title' => $this->t('Force push even if this creates duplicate notification'),
        '#title_display' => $forEdit ? 'after' : 'hidden',
      ],
      'silent' => [
        '#type' => 'checkbox',
        '#default_value' => $config->get($topic->getSettingKey('silent')) ?? FALSE,
        '#title' => $this->t('Make this silent and do not create any notifications'),
        '#title_display' => $forEdit ? 'after' : 'hidden',
        '#description' => $this->t('If this is checked, the other options have no effect.'),
      ],
    ];
  }

  /**
   * Gets the defaults subscription labels.
   *
   * @return array
   *   The list of default subscription labels.
   */
  protected function defaultSubscriptionLabels(): array {
    return [
      'allow_subscription' => $this->t('Allow entity type subscription'),
      'allow_entity_subscription' => $this->t('Allow individual entity subscription'),
      'allow_related_entity_subscription' => $this->t('Allow related entity subscription'),
    ];
  }

  /**
   * Gets the default subscriptions.
   *
   * @param array $roles
   *   List of user roles.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The DANSE config.
   * @param \Drupal\danse_content\Topic\TopicInterface $topic
   *   The topic.
   * @param bool $forEdit
   *   Whether the settings are for the edit form.
   *
   * @return array
   *   The form settings elements.
   */
  protected function defaultSubscriptions(array $roles, ImmutableConfig $config, TopicInterface $topic, bool $forEdit): array {
    $createMode = $topic->id() === $topic::CREATE;
    return [
      'allow_subscription' => [
        '#type' => 'select',
        '#options' => $roles,
        '#multiple' => TRUE,
        '#default_value' => $config->get($topic->getSettingKey('allow_subscription')) ?? [],
        '#title' => $this->t('Allow entity type subscription'),
        '#title_display' => 'hidden',
        '#access' => !$forEdit,
      ],
      'allow_entity_subscription' => [
        '#type' => $createMode ? 'value' : 'select',
        '#options' => $roles,
        '#multiple' => TRUE,
        '#default_value' => $createMode ? [] : ($config->get($topic->getSettingKey('allow_entity_subscription')) ?? []),
        '#title' => $this->t('Allow individual entity subscription'),
        '#title_display' => 'hidden',
        '#access' => !$forEdit,
      ],
      'allow_related_entity_subscription' => [
        '#type' => 'select',
        '#options' => $roles,
        '#multiple' => TRUE,
        '#default_value' => $config->get($topic->getSettingKey('allow_related_entity_subscription')) ?? [],
        '#title' => $this->t('Allow related entity subscription'),
        '#title_display' => 'hidden',
        '#access' => !$forEdit,
      ],
    ];
  }

}
