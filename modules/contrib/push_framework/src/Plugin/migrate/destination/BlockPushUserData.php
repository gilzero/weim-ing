<?php

namespace Drupal\push_framework\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\push_framework\Service;
use Drupal\user\Plugin\migrate\destination\UserData;

/**
 * Migration destination plugin to store push blocks as user data.
 *
 * @MigrateDestination(
 *   id = "push_framework_user_data"
 * )
 */
class BlockPushUserData extends UserData {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): array {
    $uid = $row->getDestinationProperty('uid');
    $module = 'push_framework';
    $key = Service::BLOCK_PUSH;
    $value = (bool) $row->getDestinationProperty('flag');
    $this->userData->set($module, $uid, $key, $value);

    return [$uid, $module, $key];
  }

}
