<?php

namespace Drupal\site_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Site Audit Checklist item annotation object.
 *
 * @see \Drupal\site_audit\Plugin\SiteAuditChecklistManager
 * @see plugin_api
 *
 * @Annotation
 */
class SiteAuditChecklist extends Plugin {


  /**
   * The unique checklist ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label/name of the checklist.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the checklist.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
