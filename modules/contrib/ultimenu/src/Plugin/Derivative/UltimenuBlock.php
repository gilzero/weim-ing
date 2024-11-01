<?php

namespace Drupal\ultimenu\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ultimenu\UltimenuManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for Ultimenu.
 *
 * @see \Drupal\ultimenu\Plugin\Block\UltimenuBlock
 */
class UltimenuBlock extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The Plugin service.
   *
   * @var \Drupal\ultimenu\UltimenuManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new UltimenuBlock.
   *
   * @param \Drupal\ultimenu\UltimenuManagerInterface $manager
   *   The ultimenu manager.
   */
  public function __construct(UltimenuManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('ultimenu.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Provide a block plugin definition for each ultimenu.
    foreach ($this->manager->getUltimenuBlocks() as $key => $name) {
      $menu_key = 'ultimenu-' . $key;
      $this->derivatives[$menu_key] = $base_plugin_definition;
      $this->derivatives[$menu_key]['admin_label'] = $this->t("Ultimenu: @name", ['@name' => $name]);
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
