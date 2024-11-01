<?php

namespace Drupal\moderation_dashboard\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Views field which provides a link to the latest version of an Entity.
 */
#[ViewsField("link_to_latest_version")]
class LinkToLatestVersion extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager, protected ModerationInformation $moderation_information) {
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
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): string|MarkupInterface {
    $moderation_information = $this->moderation_information;

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity($values);
    $entity_type_id = $entity->getEntityTypeId();

    if (
      $moderation_information->isModeratedEntity($entity) &&
      $moderation_information->hasPendingRevision($entity)
    ) {

      $node_storage = $this->entityTypeManager->getStorage('node');
      $latest_revision_id = $node_storage->getLatestRevisionId($entity->id());
      $entity = $node_storage->loadRevision($latest_revision_id);

      $build = [
        '#title' => $entity->label(),
        '#type' => 'link',
        '#url' => Url::fromRoute("entity.{$entity_type_id}.latest_version", [
          $entity_type_id => $entity->id(),
        ]),
      ];
    }
    else {
      $build = [
        '#title' => $entity->label(),
        '#type' => 'link',
        '#url' => $entity->toLink()->getUrl(),
      ];
    }

    return $this->renderer->render($build);
  }

}
