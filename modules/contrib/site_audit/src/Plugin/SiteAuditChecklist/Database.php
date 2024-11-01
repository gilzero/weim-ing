<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Database Report.
 *
 * @SiteAuditChecklist(
 *  id = "database",
 *  name = @Translation("Database"),
 *  description = @Translation("Drupal Database Best Practices and Settings")
 * )
 */
class Database extends SiteAuditChecklistBase {}
