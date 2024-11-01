<?php

namespace Drupal\danse_content\Plugin\views\field;

use Drupal\danse_content\Service;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field for the entity subscription widget.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("danse_subscription")
 */
class Subscription extends FieldPluginBase {

  /**
   * The DANSE services.
   *
   * @var \Drupal\danse_content\Service
   */
  protected Service $service;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): Subscription {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->service = $container->get('danse_content.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /**
     * @var \Drupal\Core\Entity\ContentEntityInterface $entity
     */
    $entity = $values->_entity;
    $elements = _danse_content_service()->widget($entity);
    return $this->renderer->render($elements);
  }

}
