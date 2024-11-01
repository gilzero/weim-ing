<?php

namespace Drupal\push_framework\Plugin\Action;

/**
 * Block all push notifications for a user.
 *
 * @Action(
 *   id = "push_framework_notifications_block",
 *   label = @Translation("Block all push notifications"),
 *   type = "user"
 * )
 */
class NotificationsBlock extends NotificationsBase {

  /**
   * {@inheritdoc}
   */
  protected bool $flag = TRUE;

}
