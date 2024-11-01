<?php

namespace Drupal\site_audit;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Renderer.
 */
abstract class Renderer {

  use StringTranslationTrait;

  /**
   * The Report to be rendered.
   *
   * @var \Drupal\site_audit\Report
   */
  public $checklist;

  /**
   * The logger we are using for output.
   */
  public $logger;

  /**
   * Any options that have been passed in.
   */
  public $options;

  /**
   * Output interface.
   */
  public $output;

  /**
   *
   */
  public function __construct($checklist, $logger, $options, $output) {
    $this->checklist = $checklist;
    $this->logger = $logger;
    $this->options = $options;
    $this->output = $output;
  }

  /**
   *
   */
  abstract public function render($detail = FALSE);

}
