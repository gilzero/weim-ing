<?php

namespace Drupal\saml_sp\SAML;

use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Settings;

/**
 * Constructs the authentication request.
 */
class SamlSPAuthnRequest extends AuthnRequest {

  /**
   * {@inheritdoc}
   */
  public function __construct(Settings $settings, $forceAuthn = FALSE, $isPassive = FALSE, $setNameIdPolicy = TRUE, $nameIdValueReq = NULL) {
    parent::__construct($settings, $forceAuthn, $isPassive, $setNameIdPolicy, $nameIdValueReq);
    if (\Drupal::config('saml_sp.settings')->get('debug')) {
      _saml_sp__debug('samlp:AuthnRequest', $this);
    }
  }

}
