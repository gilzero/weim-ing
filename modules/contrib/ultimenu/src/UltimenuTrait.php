<?php

namespace Drupal\ultimenu;

/**
 * A Trait common for Ultimenu split services.
 */
trait UltimenuTrait {

  /**
   * {@inheritdoc}
   */
  public function getSetting($key = NULL) {
    return $this->configFactory->get('ultimenu.settings')->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeDefault() {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
