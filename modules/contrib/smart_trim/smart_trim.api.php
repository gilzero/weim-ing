<?php

/**
 * @file
 * Smart Trim module hook definitions.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the Smart Trim read more link.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that is the read more link is for.
 * @param string $more
 *   The Smart Trim read more text.
 * @param \Drupal\Core\Link $url
 *   The Url that the link will point to.
 *
 * @ingroup smart_trim
 */
function hook_smart_trim_link_modify(EntityInterface $entity, string &$more, Link &$url) {
}

/**
 * @} End of "addtogroup hooks".
 */
