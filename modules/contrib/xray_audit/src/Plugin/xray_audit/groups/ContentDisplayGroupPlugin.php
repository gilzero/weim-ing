<?php

namespace Drupal\xray_audit\Plugin\xray_audit\groups;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginBase;

/**
 * Plugin implementation of the xray_audit_group_plugin.
 *
 * @XrayAuditGroupPlugin (
 *   id = "content_display",
 *   label = @Translation("Content Display"),
 *   description = @Translation("Information regarding how the contents are shown to the user."),
 *   sort = 5
 * )
 */
class ContentDisplayGroupPlugin extends XrayAuditGroupPluginBase {

}
