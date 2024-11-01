<?php

namespace Drupal\saml_sp\SAML;

use OneLogin\Saml2\Response;

/**
 * Provides the response.
 */
class SamlSPResponse extends Response {

  /**
   * Verifies that the document has the expected signed nodes.
   */
  public function validateSignedElements($signedElements) {
    if (count($signedElements) > 2) {
      return FALSE;
    }
    $ocurrence = array_count_values($signedElements);

    if (
        (in_array('samlp:Response', $signedElements) && $ocurrence['samlp:Response'] > 1) ||
        (in_array('saml:Assertion', $signedElements) && $ocurrence['saml:Assertion'] > 1) ||
        (in_array('Assertion', $signedElements) && $ocurrence['Assertion'] > 1) ||
        (
          !in_array('samlp:Response', $signedElements) &&
          !in_array('saml:Assertion', $signedElements) &&
          !in_array('Assertion', $signedElements)
        )
    ) {
      return FALSE;
    }
    return TRUE;
  }

}
