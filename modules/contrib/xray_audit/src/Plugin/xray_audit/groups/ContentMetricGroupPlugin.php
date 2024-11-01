<?php

namespace Drupal\xray_audit\Plugin\xray_audit\groups;

use Drupal\xray_audit\Plugin\XrayAuditGroupPluginBase;

/**
 * Plugin implementation of the xray_audit_group_plugin.
 *
 * @XrayAuditGroupPlugin (
 *   id = "content_metric",
 *   label = @Translation("Content Metrics"),
 *   description = @Translation("Quantitative measurements of content in data base."),
 *   sort = 10
 * )
 */
class ContentMetricGroupPlugin extends XrayAuditGroupPluginBase {

}
