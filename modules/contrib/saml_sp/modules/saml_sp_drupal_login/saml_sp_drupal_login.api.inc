<?php

/**
 * @file
 * API hooks for SAML Service Provider Drupal Login module.
 */

use Drupal\user\UserInterface;

/**
 * Allow the user to be altered based on attributes passed in from the SAML IdP.
 *
 * @param \Drupal\user\UserInterface $user
 *   The logged-in user.
 * @param array $attributes
 *   User attributes as reported by the IdP.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function hook_saml_sp_drupal_login_user_attributes(UserInterface $user, array $attributes) {
  // Do something with the user account.
  // Make sure you save the $user object or the changes won't stick.
  $user->save();
}
