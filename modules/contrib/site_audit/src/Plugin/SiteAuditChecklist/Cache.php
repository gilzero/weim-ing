<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Cache Report.
 *
 * @SiteAuditChecklist(
 *  id = "cache",
 *  name = @Translation("Cache"),
 *  description = @Translation("Drupal's caching settings")
 * )
 */
class Cache extends SiteAuditChecklistBase {}
