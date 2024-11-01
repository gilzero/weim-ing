<?php

/**
 * @file
 * Hooks provided by the SMTP Multiple module
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the data of an SMTP message according to the email key.
 *
 * Instead of using this hook, one can add settings to one's settings file
 * directly like so:
 * $settings['smtp_multiple_config']['email_key']['smtp_host'] = 'smtp.gmail.com';
 *
 * @param array &$config
 *
 * @param string $key
 */
function hook_smtp_multiple_config_alter(array &$config, $key) {

  if ($key === 'comment_notify_comment_notify_mail') {
    $config['smtp_host'] = 'smtp.gmail.com';
    $config['smtp_port'] = '465';
    $config['smtp_protocol'] = 'ssl';
    $config['smtp_username'] = 'user@gmail.com';
    $config['smtp_password'] = 'pass';
    $config['smtp_from'] = 'user@gmail.com';
    $config['smtp_fromname'] = 'Me';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
