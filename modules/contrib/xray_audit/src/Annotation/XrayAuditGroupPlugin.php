<?php

namespace Drupal\xray_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines Groups annotation object.
 *
 * @Annotation
 */
class XrayAuditGroupPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The position of the group in the group list.
   *
   * @var int
   */
  public $sort;

}
