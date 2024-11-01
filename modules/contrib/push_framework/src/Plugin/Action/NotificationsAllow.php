<?php

namespace Drupal\push_framework\Plugin\Action;

/**
 * Allow all push notifications for a user.
 *
 * @Action(
 *   id = "push_framework_notifications_allow",
 *   label = @Translation("Allow all push notifications"),
 *   type = "user"
 * )
 */
class NotificationsAllow extends NotificationsBase {

  /**
   * {@inheritdoc}
   */
  protected bool $flag = FALSE;

}
