<?php

namespace Drupal\xray_audit\Plugin\xray_audit\groups;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginBase;

/**
 * Plugin implementation of the xray_audit_group_plugin.
 *
 * @XrayAuditGroupPlugin (
 *   id = "package",
 *   label = @Translation("Packages"),
 *   description = @Translation("Modules, packages, libraries used by the site.."),
 *   sort = 30
 * )
 */
class PackageGroupPlugin extends XrayAuditGroupPluginBase {

}
