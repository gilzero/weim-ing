<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\danse_content\SubscriptionOperation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The topic base class.
 *
 * @package Drupal\danse_content\Topic
 */
abstract class TopicBase implements TopicInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Topic constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  final public function __construct(ModuleHandlerInterface $module_handler, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TopicInterface {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingKey(string $key): string {
    return implode('.', ['events', $key, $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function accessToDefaultSettings(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function excludedOnEntityTypes(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function dependencies(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function assert(string $entity_type_id): bool {
    if (!in_array($entity_type_id, $this->excludedOnEntityTypes(), TRUE)) {
      foreach ($this->dependencies() as $dependency) {
        if (!$this->moduleHandler->moduleExists($dependency)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function operationLabel(ContentEntityInterface $entity, bool $subscribe, string $subscription_mode): string {
    $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());
    $entityHasBundle = $entity->getEntityTypeId() !== $entity->bundle();
    $args = [
      '@entityType' => $entity->getEntityType()->getBundleLabel(),
      '@bundle' => $bundleInfo[$entity->bundle()]['label'],
      '@action' => $this->actionForLabel(),
      '@title' => $entity->label(),
    ];
    $p0 = $subscribe ?
      'subscribe' :
      'unsubscribe';
    $p1 = $entityHasBundle ?
      'WithBundle' :
      'WithoutBundle';
    switch ($subscription_mode) {
      case SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE:
        $p2 = 'ForEntityType';
        break;

      case SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY:
        $p2 = 'ForEntity';
        break;

      case SubscriptionOperation::SUBSCRIPTION_MODE_RELATED_ENTITY:
        $p2 = 'ForRelatedEntity';
        break;

      default:
        $p2 = 'ForEntity';
    }
    $function = $p0 . $p1 . $p2 . 'Label';
    $label = $this->{$function}($args);
    $context = [
      'topic' => $this,
      'entity' => $entity,
      'subscribeMode' => $subscribe,
      'subscriptionMode' => $subscription_mode,
      'entityHasBundle' => $entityHasBundle,
    ];
    $this->moduleHandler->alter('danse_content_topic_operation_label', $label, $args, $context);
    return $label;
  }

  /**
   * Get subscription label for related entity without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithoutBundleForRelatedEntityLabel(array $args): string {
    return (string) $this->t('Subscribe to related content to this @bundle when it gets @action', $args);
  }

  /**
   * Get subscription label for entity without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithoutBundleForEntityLabel(array $args): string {
    return (string) $this->t('Subscribe to this @bundle when it gets @action', $args);
  }

  /**
   * Get subscription label for entity type without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithoutBundleForEntityTypeLabel(array $args): string {
    return (string) $this->t('Subscribe to all @bundle content when it gets @action', $args);
  }

  /**
   * Get subscription label for related entity type without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithBundleForRelatedEntityLabel(array $args): string {
    return $this->subscribeWithoutBundleForRelatedEntityLabel($args);
  }

  /**
   * Get subscription label for entity with bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithBundleForEntityLabel(array $args): string {
    return $this->subscribeWithoutBundleForEntityLabel($args);
  }

  /**
   * Get subscription label for entity with bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function subscribeWithBundleForEntityTypeLabel(array $args): string {
    return $this->subscribeWithoutBundleForEntityTypeLabel($args);
  }

  /**
   * Get unsubscription label for related entity without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithoutBundleForRelatedEntityLabel(array $args): string {
    return (string) $this->t('Unsubscribe from related content to this @bundle when it gets @action', $args);
  }

  /**
   * Get unsubscription label for entity without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithoutBundleForEntityLabel(array $args): string {
    return (string) $this->t('Unsubscribe from this @bundle when it gets @action', $args);
  }

  /**
   * Get unsubscription label for entity type without bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithoutBundleForEntityTypeLabel(array $args): string {
    return (string) $this->t('Unsubscribe from all @bundle content when it gets @action', $args);
  }

  /**
   * Get unsubscription label for related entity with bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithBundleForRelatedEntityLabel(array $args): string {
    return $this->unsubscribeWithoutBundleForRelatedEntityLabel($args);
  }

  /**
   * Get unsubscription label for entity with bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithBundleForEntityLabel(array $args): string {
    return $this->unsubscribeWithoutBundleForEntityLabel($args);
  }

  /**
   * Get unsubscription label for entity type with bundles.
   *
   * @param array $args
   *   Label arguments.
   *
   * @return string
   *   The label.
   */
  protected function unsubscribeWithBundleForEntityTypeLabel(array $args): string {
    return $this->unsubscribeWithoutBundleForEntityTypeLabel($args);
  }

}
