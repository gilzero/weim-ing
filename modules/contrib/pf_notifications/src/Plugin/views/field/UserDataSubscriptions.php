<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\user\UserDataInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides User data > DANSE > Push Subscriptions field handler.
 *
 * @ViewsField("pf_notifications_user_data_subscriptions")
 *
 * @phpstan-consistent-constructor
 */
class UserDataSubscriptions extends FieldPluginBase implements MultiItemsFieldHandlerInterface {

  /**
   * Constructs a UserDataSubscriptions object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected UserDataInterface $userData,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BaseInterface $service,
  ) {
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
      $container->get('user.data'),
      $container->get('entity_type.manager'),
      $container->get('pf_notifications.base')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // For non-existent columns (i.e. computed fields)
    // this method must be empty.
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item): string|ViewsRenderPipelineMarkup { // phpcs:ignore
    if (is_array($item)) {
      return Json::encode($item);
    }
    return $this->sanitizeValue((string) $item);
  }

  /**
   * {@inheritdoc}
   */
  public function renderItems($items): string|MarkupInterface {

    if (!empty($items)) {
      $header = [
        'entity' => $this->t('Entity'),
        'parent_entity' => $this->t('Parent entity'),
        'parent_comment' => $this->t('Parent comment'),
        'client' => $this->t('Client'),
        'operations' => $this->t('Operations'),
      ];
      $build = [
        '#theme' => 'table',
        '#header' => $header,
        '#header_columns' => 4,
      ];

      $row = [];
      foreach ($items as $item) {
        $value = is_string($item) ? Json::decode($item) : $item;
        $delete_options = [
          'target' => '_blank',
          'query' => [
            'token' => $value['token'],
            'danse_key' => $value['danse_key'],
            'uid' => $value['uid'],
          ],
        ];

        $row[] = [
          'entity' => [
            'data' => [
              '#markup' => $value['entity'] ?? '',
            ],
          ],
          'parent_entity' => [
            'data' => [
              '#markup' => $value['parent_entity'] ?? '',
            ],
          ],
          'parent_comment' => [
            'data' => [
              '#markup' => $value['parent_comment'] ?? '',
            ],
          ],
          'client' => [
            'data' => [
              '#markup' => $value['client'] ?? '',
            ],
          ],
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'delete' => [
                  'title' => $this->t('Delete'),
                  'url' => Url::fromRoute('pf_notifications.remove_subscription', [], $delete_options),
                ],
              ],
            ],
          ],
        ];
      }
      $build['#rows'] = $row;
      return $this->getRenderer()->render($build);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values): array {
    $items = [];
    $uid = (int) ($values->uid ?? 0);
    $user_data = $this->service->getUserData()->get('danse', $uid);

    if (!$user_data) {
      return $items;
    }
    if (isset($user_data[$uid]) && empty($user_data[$uid])) {
      return $items;
    }

    $danse_key = $this->view->args[1] ?? NULL;
    $subscriptions = $danse_key ? ($this->service->getSubscriptions((int) $uid, $danse_key) ?: []) : [];
    $property = BaseInterface::PROPERTY;
    if (empty($subscriptions)) {
      foreach ($user_data as $key => $data) {
        if (isset($data[$property])) {
          foreach ($data[$property] as &$item) {
            if (is_array($item) && isset($item['entity_id']) && isset($item['endpoint'])) {
              $entity_id = $item['entity_id'];
              $this->prepareItems($uid, $key, $item);
              $items[$entity_id] = $item;
            }
          }
        }
      }
    }
    else {
      foreach ($subscriptions as &$item) {
        if (is_array($item) && isset($item['entity_id']) && isset($item['endpoint'])) {
          $entity_id = $item['entity_id'];
          $this->prepareItems($uid, $danse_key, $item);
          $items[$entity_id] = $item;
        }
      }
    }
    return $items;
  }

  /**
   * Prepare table cell values.
   *
   * @param int $uid
   *   Id of the user in question.
   * @param string $danse_key
   *   A unique DANSE key, name column in users_data table.
   * @param array<string, string> $subscription
   *   Referenced array containing push notification subscription data.
   */
  protected function prepareItems(int $uid, string $danse_key, array &$subscription): void {
    if (isset($subscription['entity_type']) && isset($subscription['entity_id'])) {
      $entity_links = $this->service->entityLinks($subscription, $subscription['endpoint']);
      if (isset($subscription['endpoint'])) {
        $subscription = [
          'token' => $subscription['token'],
          'uid' => $uid,
          'danse_key' => $danse_key,
          'entity' => $entity_links['entity']->toString(),
          'parent_entity' => isset($entity_links['parent']) ? $entity_links['parent']->toString() : '',
          'parent_comment' => isset($entity_links['parent_comment']) ? $entity_links['parent_comment']->toString() : '',
          'client' => $entity_links['client']->toString(),
        ];
      }
    }
  }

}
