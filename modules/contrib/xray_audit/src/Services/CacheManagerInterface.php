<?php

namespace Drupal\xray_audit\Services;

/**
 * Cache manager interface.
 */
interface CacheManagerInterface {

  /**
   * Set cache with invalidation by tags.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $value
   *   The data to store in the cache.
   * @param array $tags
   *   The tags to invalidate.
   */
  public function setCacheTagsInv(string $cid, $value, $tags = []);

  /**
   * Set cache with temporal invalidation.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $value
   *   The data to store in the cache.
   * @param int $duration
   *   The cache object duration.
   */
  public function setCacheTempInv(string $cid, $value, int $duration);

  /**
   * Get cached data.
   *
   * @param string $cid
   *   The cache ID to retrieve.
   *
   * @return mixed
   *   The cached data or FALSE on failure.
   */
  public function getCachedData(string $cid);

  /**
   * Clear all cached objects.
   */
  public function clearAllCache();

  /**
   * Delete all cached objects.
   *
   * @param string $cid
   *   The cache ID to delete.
   */
  public function deleteCache(string $cid);

  /**
   * Invalidate cache.
   *
   * @param string $cid
   *   The cache ID to invalidate.
   */
  public function invalidateCache(string $cid);

  /**
   * Remove the bin.
   */
  public function removeBin();

}
