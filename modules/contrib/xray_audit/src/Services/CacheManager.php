<?php

namespace Drupal\xray_audit\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Service to manage the Xray Audit cache.
 */
class CacheManager implements CacheManagerInterface {

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The cache service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(CacheBackendInterface $cache, TimeInterface $time) {
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheTagsInv(string $cid, $value, $tags = []) {
    $this->cache->set($cid, $value, CacheBackendInterface::CACHE_PERMANENT, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheTempInv(string $cid, $value, int $duration) {
    $expired = $this->time->getCurrentTime() + $duration;
    $this->cache->set($cid, $value, $expired);
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedData(string $cid) {
    $cache_object = $this->cache->get($cid);
    if (empty($cache_object)) {
      return NULL;
    }
    return $cache_object->data ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAllCache() {
    $this->cache->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCache(string $cid) {
    $this->cache->delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateCache(string $cid) {
    $this->cache->invalidate($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->cache->removeBin();
  }

}
