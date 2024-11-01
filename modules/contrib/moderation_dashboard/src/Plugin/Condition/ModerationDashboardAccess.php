<?php

namespace Drupal\moderation_dashboard\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Moderation Dashboard Access' condition.
 */
#[Condition(
  id: "moderation_dashboard_access",
  label: new TranslatableMarkup("Moderation Dashboard Access"),
  context_definitions: [
    "dashboard_user" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("Dashboard owner"),
      required: FALSE,
    ),
    "current_user" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("Current user"),
    ),
  ],
)]
class ModerationDashboardAccess extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor for DI.
   *
   * @param array $configuration
   *   A config array.
   * @param string $plugin_id
   *   Contains plugin ID.
   * @param mixed $plugin_definition
   *   Contains plugin definition.
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   Contains User entity storage interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected UserStorageInterface $userStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enable' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['enable'] = [
      '#title' => $this->t('Enable'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable'],
      '#description' => $this->t('Leaving this unchecked will bypass this condition.'),
      '#weight' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['enable'] = $form_state->getValue('enable', FALSE);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  public function evaluate() {
    if (!$this->configuration['enable']) {
      return TRUE;
    }

    $dashboard_owner = $this->getContextValue('dashboard_user');
    $current_user = $this->getContextValue('current_user');
    if (is_string($dashboard_owner)) {
      $dashboard_owner = $this->userStorage->load($dashboard_owner);
    }
    // If the given user doesn't have a dashboard, nobody can view it.
    if (!$dashboard_owner->hasPermission('use moderation dashboard')) {
      return FALSE;
    }
    // If the current user is on their own dashboard, they can view it.
    if ($current_user->id() === $dashboard_owner->id()) {
      return TRUE;
    }
    // But they can only view the dashboard of others with another permission.
    return $current_user->hasPermission('view any moderation dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t("User can't access moderation dashboard.");
    }
    return $this->t('User can access moderation dashboard.');
  }

}
