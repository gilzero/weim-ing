<?php

/**
 * @file
 * Hooks related to the Disable Login module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the secret key to allow login page access.
 *
 * This hook is called when a user tries to access the /user/login page.
 *
 * You can implement this hook to alter the value of the secret key
 * used to allow access to the /user/login page when the Disable Login
 * module is enabled on the site.
 *
 * @param string $secret
 *   The secret key to be used.
 */
function hook_disable_login_key_alter(&$secret) {
  // You can build your custom logic to set the secret key here.
  $secret = 'altered-secret-key-value';
}

/**
 * @} End of "addtogroup hooks".
 */
