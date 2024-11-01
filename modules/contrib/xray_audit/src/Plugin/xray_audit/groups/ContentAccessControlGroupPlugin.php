<?php

namespace Drupal\xray_audit\Plugin\xray_audit\groups;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginBase;

/**
 * Plugin implementation of the xray_audit_group_plugin.
 *
 * @XrayAuditGroupPlugin (
 *   id = "content_access_control",
 *   label = @Translation("Content Access Control and Users"),
 *   description = @Translation("Information about access system, for example, roles, permissions."),
 *   sort = 15
 * )
 */
class ContentAccessControlGroupPlugin extends XrayAuditGroupPluginBase {

}
