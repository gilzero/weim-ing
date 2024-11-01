<?php

namespace Drupal\site_audit_report_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a site audit report entity type.
 */
interface SiteAuditReportInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
