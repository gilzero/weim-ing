<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides an Extensions Report.
 *
 * @SiteAuditChecklist(
 *  id = "extensions",
 *  name = @Translation("Extensions"),
 *  description = @Translation("Drupal's Extensions")
 * )
 */
class Extensions extends SiteAuditChecklistBase {}
