<?php

namespace Drupal\eca_cache\Plugin\Action;

/**
 * Action to invalidate raw cache.
 *
 * @Action(
 *   id = "eca_raw_cache_invalidate",
 *   label = @Translation("Cache Raw: invalidate"),
 *   description = @Translation("Invalidates a part or the whole raw cache."),
 *   eca_version_introduced = "2.0.0"
 * )
 */
class RawCacheInvalidate extends CacheInvalidate {

}
