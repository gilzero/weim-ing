<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Security Report.
 *
 * @SiteAuditChecklist(
 *  id = "security",
 *  name = @Translation("Security"),
 *  description = @Translation("Security settings and recmomendations")
 * )
 */
class Security extends SiteAuditChecklistBase {}
