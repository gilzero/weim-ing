<?php

namespace Drupal\ultimenu\Ajax;

use Drupal\Core\Ajax\HtmlCommand;

/**
 * Overrides core HtmlCommand.
 *
 * @ingroup ultimenu
 */
class UltimenuHtmlCommand extends HtmlCommand {

  /**
   * The caller for the method to reduce deep checks, to deal with AJAX errors.
   *
   * @var string
   */
  protected $caller;

  /**
   * Overrides an HtmlCommand object.
   */
  public function __construct($selector, $content, ?array $settings = NULL, $caller = 'region') {
    parent::__construct($selector, $content, $settings);
    $this->caller = $caller;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'ultimenu' => $this->caller,
    ] + parent::render();
  }

}
