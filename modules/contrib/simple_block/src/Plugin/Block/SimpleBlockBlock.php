<?php

namespace Drupal\simple_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\simple_block\Entity\SimpleBlock;
use Drupal\simple_block\SimpleBlockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block config type.
 *
 * @Block(
 *  id = "simple_block",
 *  admin_label = @Translation("Simple block"),
 *  category = @Translation("Simple block"),
 *  deriver = "Drupal\simple_block\Plugin\Derivative\SimpleBlock",
 * )
 */
class SimpleBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The simple block entity.
   *
   * @var \Drupal\simple_block\SimpleBlockInterface
   */
  protected $entity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new block plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    $label = '';

    if ($simple_block = $this->getEntity()) {
      $label = $simple_block->label();
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if (!$simple_block = $this->getEntity()) {
      return [
        '#markup' => $this->t('Simple block with ID %id does not exist. <a href=":url">Add simple block</a>.', [
          '%id' => $this->getDerivativeId(),
          ':url' => Url::fromRoute('simple_block.form_add')->toString(),
        ]),
        '#access' => $this->currentUser->hasPermission('administer blocks'),
      ];
    }

    $content = $simple_block->getContent();
    return [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($content['value']),
      '#format' => $content['format'],
      '#contextual_links' => [
        'simple_block' => [
          'route_parameters' => ['simple_block' => $simple_block->id()],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    /** @var \Drupal\simple_block\Entity\SimpleBlock $simple_block */
    if ($simple_block = SimpleBlock::load($this->getDerivativeId())) {
      $cache_tags = Cache::mergeTags($cache_tags, $simple_block->getCacheTags());
      if (!empty($format_id = $simple_block->getContent()['format'])) {
        if ($format = $this->entityTypeManager->getStorage('filter_format')->load($format_id)) {
          $cache_tags = Cache::mergeTags($cache_tags, $format->getCacheTags());
        }
      }
    }
    return $cache_tags;
  }

  /**
   * Loads the block config entity of the block, if any.
   *
   * @return \Drupal\simple_block\SimpleBlockInterface|null
   *   The block config entity.
   */
  protected function getEntity(): ?SimpleBlockInterface {
    if (!isset($this->entity)) {
      $this->entity = $this->entityTypeManager->getStorage('simple_block')->load($this->getDerivativeId());
    }
    return $this->entity;
  }

}
