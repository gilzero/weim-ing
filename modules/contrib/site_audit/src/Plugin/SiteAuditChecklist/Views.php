<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Views Report.
 *
 * @SiteAuditChecklist(
 *  id = "views",
 *  name = @Translation("Views"),
 *  description = @Translation("Views Best Practices")
 * )
 */
class Views extends SiteAuditChecklistBase {}
