<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Views Report.
 *
 * @SiteAuditChecklist(
 *  id = "watchdog",
 *  name = @Translation("Watchdog database logs"),
 *  description = @Translation("")
 * )
 */
class Watchdog extends SiteAuditChecklistBase {}
