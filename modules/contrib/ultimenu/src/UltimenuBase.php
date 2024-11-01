<?php

namespace Drupal\ultimenu;

use Drupal\blazy\BlazyBase;

/**
 * Provides Ultimenu utility methods.
 */
abstract class UltimenuBase extends BlazyBase implements UltimenuInterface {

  use UltimenuTrait;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public function blockManager() {
    if (!isset($this->blockManager)) {
      $this->blockManager = $this->service('plugin.manager.block');
    }
    return $this->blockManager;
  }

}
