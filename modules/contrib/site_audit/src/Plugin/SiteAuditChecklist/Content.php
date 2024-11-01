<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Content Report.
 *
 * @SiteAuditChecklist(
 *  id = "content",
 *  name = @Translation("Content"),
 *  description = @Translation("Content Checks")
 * )
 */
class Content extends SiteAuditChecklistBase {}
