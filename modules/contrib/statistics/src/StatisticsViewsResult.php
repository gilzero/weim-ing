<?php

namespace Drupal\statistics;

/**
 * Value object for passing statistic results.
 */
class StatisticsViewsResult {

  /**
   * The total count of views for the node.
   *
   * @var int
   */
  protected int $totalCount;

  /**
   * The count of views during the latest day recorded, possible not current.
   *
   * @var int
   */
  protected int $dayCount;

  /**
   * The timestamp of the latest access update.
   *
   * @var int
   */
  protected int $timestamp;

  public function __construct($total_count, $day_count, $timestamp) {
    $this->totalCount = (int) $total_count;
    $this->dayCount = (int) $day_count;
    $this->timestamp = (int) $timestamp;
  }

  /**
   * Total number of times the entity has been viewed.
   *
   * @return int
   *   The number of views.
   */
  public function getTotalCount() {
    return $this->totalCount;
  }

  /**
   * Total number of times the entity has been viewed "today".
   *
   * @return int
   *   The number of views.
   */
  public function getDayCount() {
    return $this->dayCount;
  }

  /**
   * Timestamp of when the entity was last viewed.
   *
   * @return int
   *   The timestamp as a UNIX timestamp.
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

}
