<?php

/**
 * @file
 * API hooks for SAML Service Provider module.
 */

/**
 * Alter the settings used when generating SAML requests.
 *
 * @param array $settings
 *   The settings array for generating and reading SAML XML.
 */
function hook_saml_sp_settings_alter(array &$settings) {
  // Sample: Disable strict processing for a particular IdP.
  if ($settings['idp']['entityId'] == 'http://example.com/saml/foo') {
    $settings['strict'] = FALSE;
  }
}
