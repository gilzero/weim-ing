<?php

namespace Drupal\xray_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XrayAudit Tasks annotation object.
 *
 * @Annotation
 */
class XrayAuditTaskPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Group that this task belongs to.
   *
   * @var string
   */
  public $group;

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
   * The position in the list of the group.
   *
   * @var int
   */
  public $sort;

  /**
   * The builders methods.
   *
   * ['operation' =>  [
   *  'label' => 'Label operation method',
   *  'description' => 'Description'
   *   ]
   * ]
   *
   * @var mixed[]
   */
  public $operations;

  /**
   * Method names of batch processes.
   *
   * ['batches' =>  [
   *  'create-temporal-table' => 'createTemporalTable'
   *   ]
   * ]
   *
   * @var mixed[]
   */
  public $batches;

  /**
   * Name of the method that will be launched on module install.
   *
   * @var string
   */
  public $install;

  /**
   * Name of the method that will be launched on module uninstall.
   *
   * @var string
   */
  public $uninstall;

  /**
   * If the different operations wil show as local task.
   *
   * @var int
   */
  public $local_task;

}
