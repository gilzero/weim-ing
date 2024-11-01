<?php

namespace Drupal\moderation_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "Moderation Dashboard Add Links" block.
 */
#[Block(
  id: "moderation_dashboard_add_links",
  admin_label: new TranslatableMarkup("Moderation Dashboard Add Links"),
  category: new TranslatableMarkup("Moderation Dashboard")
)]
class ModerationDashboardAddLinks extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager) {
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#theme' => 'item_list',
      '#attributes' => [
        'class' => ['moderation-dashboard-add-links'],
      ],
      '#items' => [],
    ];

    // Only use node types the user has access to.
    $control_handler = $this->entityTypeManager->getAccessControlHandler('node');
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $type) {
      if ($control_handler->createAccess($type->id())) {
        $build['#items'][] = [
          '#type' => 'link',
          '#title' => $type->label(),
          '#url' => new Url('node.add', ['node_type' => $type->id()]),
        ];
      }
    }

    return $build;
  }

}
