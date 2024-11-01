<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a BestPractices Report.
 *
 * @SiteAuditChecklist(
 *  id = "best_practices",
 *  name = @Translation("Best practices"),
 *  description = @Translation("Drupal Best Practices")
 * )
 */
class BestPractices extends SiteAuditChecklistBase {}
