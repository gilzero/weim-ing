<?php

namespace Drupal\danse_content\Plugin\views\filter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for subscribed entities.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("danse_subscription")
 */
class Subscription extends InOperator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): Subscription {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), $this->entityTypeManager->getDefinition('user')->getListCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), $this->entityTypeManager->getDefinition('user')->getListCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(mixed &$form, FormStateInterface $form_state): void {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple(): void {
    try {
      $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    }
    catch (PluginNotFoundException | \Exception $e) {
      $entity_type = NULL;
      // @todo Log this exception.
    }
    if ($entity_type) {
      $this->ensureMyTable();
      $entity_base_table = $entity_type->getBaseTable();
      $field = implode('.', [
        $entity_base_table,
        $entity_type->getKey('id'),
      ]);
      $query = $this->database->select('users_data', 'u')
        ->fields('u', ['name'])
        ->condition('u.uid', $this->currentUser->id())
        ->condition('u.module', 'danse')
        ->condition('u.value', 1)
        ->condition('u.name', implode('-', [
          'content',
          $entity_type->id(),
          '%',
        ]), 'LIKE')
        ->execute();
      $values = [];
      if ($query) {
        foreach ($query->fetchCol() as $item) {
          if ($value = (int) explode('-', $item)[2]) {
            $values[] = $value;
          }
        }
      }
      if (empty($values)) {
        // Make sure we get an empty result for those without any subscription.
        $values = [0];
      }
      if ($this->query instanceof Sql) {
        $this->query->addWhere($this->options['group'], $field, $values, 'IN');
      }
    }
  }

}
