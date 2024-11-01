<?php

namespace Drupal\xray_audit\Plugin\xray_audit\groups;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginBase;

/**
 * Plugin implementation of the xray_audit_group_plugin.
 *
 * @XrayAuditGroupPlugin (
 *   id = "site_structure",
 *   label = @Translation("Site Structure"),
 *   description = @Translation("Organization of content, pages and navigation elements."),
 *   sort = 25
 * )
 */
class SiteStructureGroupPlugin extends XrayAuditGroupPluginBase {

}
