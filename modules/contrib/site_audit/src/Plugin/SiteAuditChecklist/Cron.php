<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Cron Report.
 *
 * @SiteAuditChecklist(
 *  id = "cron",
 *  name = @Translation("Cron"),
 *  description = @Translation("Drupal's Cron")
 * )
 */
class Cron extends SiteAuditChecklistBase {}
