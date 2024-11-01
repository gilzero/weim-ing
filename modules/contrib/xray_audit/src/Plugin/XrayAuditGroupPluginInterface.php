<?php

namespace Drupal\xray_audit\Plugin;

/**
 * Interface for xray_audit_group_plugin plugins.
 */
interface XrayAuditGroupPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated label.
   */
  public function label();

  /**
   * Returns the translated description.
   *
   * @return string
   *   The translated description.
   */
  public function getDescription();

  /**
   * The position of the group in the group list.
   *
   * @return int
   *   The position.
   */
  public function getSort();

  /**
   * Generate an array with all the taskPlugins.
   *
   * @return array
   *   Operations links.
   */
  public function getPluginTaskDefinitionsThisGroup();

}
