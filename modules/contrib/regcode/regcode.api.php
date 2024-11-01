<?php

/**
 * @file
 * Example hook functions for hooks provided by the Regcode module.
 */

use Drupal\user\UserInterface;

/**
 * Called when a registration code is used.
 *
 * @param object $code
 *   A registration code object.
 * @param \Drupal\user\UserInterface $user
 *   The user that the code was assigned to.
 */
function hook_regcode_used(object $code, UserInterface $user): void {
  if (is_object($code)) {
    \Drupal::messenger()->addStatus(t('Thanks %name, the code %code was used.', [
      '%name' => $user->name,
      '%code' => $code['code'],
    ]));
  }
}

/**
 * Change the regcode data that is being loaded.
 *
 * @param array $code
 *   An array of registration code objects, indexed by the registration code ID.
 *   Each element of the array is a regcode \stdClass object. You can change the
 *   properties of individual registration codes.
 *
 *   For reasons of backward compatibility and historical reasons, this hook is
 *   called from two places.
 *
 *   Entity API module loads the data directly from the database into stdClass
 *   objects array, and invokes hook_regcode_load().
 *
 *   In older versions of this module, there was hook already to alter
 *   individual regcode values. Since the arguments of the hook implementations
 *   are not consistent, reg code module now calls the hook_regcode_load() hook
 *   as an array. Note that adding or removing registration codes will work only
 *   if the caller expects multiple objects.
 *
 *   If you need to replace and regcode that is being loaded, the array key
 *   should not be changed.
 *
 *   Note that tags are not loaded when the hook is invoked by the entity API
 *   module.
 */
function hook_regcode_load(array $code): void {
  foreach ($code as $rid => $regcode) {
    /*
     * Do not change $rid or $regcode->rid.
     */

    // Feel free to change other properties.
    $regcode->group = 'foo';
  }
}

/**
 * Called when a new registration code is created.
 *
 * @param object $code
 *   A registration code object.
 */
function hook_regcode_presave(object $code): void {
  if ($code) {
    // $code->rid is not valid until AFTER the save is complete, so we can't
    // use it in this presave hook.
    \Drupal::messenger()->addStatus(t('The code "@code" was created.', ['@code' => $code->code]));
  }
}
