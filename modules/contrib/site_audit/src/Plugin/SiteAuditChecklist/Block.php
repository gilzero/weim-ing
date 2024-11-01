<?php

namespace Drupal\site_audit\Plugin\SiteAuditChecklist;

use Drupal\site_audit\Plugin\SiteAuditChecklistBase;

/**
 * Provides a Block Report.
 *
 * @SiteAuditChecklist(
 *  id = "block",
 *  name = @Translation("Block"),
 *  description = @Translation("Drupal's Blocks")
 * )
 */
class Block extends SiteAuditChecklistBase {}
