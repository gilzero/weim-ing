<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Database Report.
 *
 * @SiteAuditChecklist(
 *  id = "codebase",
 *  name = @Translation("Codebase"),
 *  description = @Translation("Drupal Codebase Best Practices and Settings")
 * )
 */
class Codebase extends SiteAuditChecklistBase {}
