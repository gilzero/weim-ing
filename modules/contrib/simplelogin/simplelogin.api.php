<?php

/**
 * @file
 * Hooks provided by the simplelogin module.
 */

/**
 * @addtogroup simplelogin
 * @{
 */

/**
 * Alter simplelogin paths.
 *
 * @param array $paths
 *   List of supported simplelogin paths.
 */
function hook_simplelogin_paths_alter(array &$paths) {
  // Add a custom user sso path.
  $paths[] = '/user/sso';
}

/**
 * @} End of "addtogroup hooks".
 */
