<?php

namespace Drupal\xray_audit_insight\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XrayAuditInsight annotation object.
 *
 * @Annotation
 */
class XrayAuditInsightPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label of the plugin.
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
   * The position in the list of all plugins.
   *
   * @var int
   */
  public $sort;

}
