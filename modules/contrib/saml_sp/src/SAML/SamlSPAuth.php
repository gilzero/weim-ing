<?php

namespace Drupal\saml_sp\SAML;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles the authentication.
 */
class SamlSPAuth extends Auth {

  use StringTranslationTrait;
  /**
   * Callback function for after the response is returned.
   *
   * @var callable
   */
  public $authCallback;

  /**
   * Settings object for the SAML library.
   *
   * @var \OneLogin\Saml2\Settings
   */
  private $samlSettings;

  /**
   * {@inheritdoc}
   *
   * This is necessary because we can’t access the private attribute on the
   * parent class.
   */
  // @codingStandardsIgnoreLine because the parent class isn’t Drupal.
  private $_lastRequestID;

  /**
   * {@inheritdoc}
   *
   * Since $this->_settings is private we need to capture the settings in our
   * own variable.
   */
  public function __construct($oldSettings = NULL) {
    $this->samlSettings = new Settings($oldSettings);
    parent::__construct($oldSettings);
  }

  /**
   * Set the auth callback for after the response is returned.
   *
   * @param string $callback
   *   The name of the callback function.
   */
  public function setAuthCallback($callback) {
    $this->authCallback = $callback;
  }

  /**
   * Initiates the SSO process.
   *
   * {@inheritdoc}
   */
  public function login($returnTo = NULL, array $parameters = [], $forceAuthn = FALSE, $isPassive = FALSE, $stay = FALSE, $setNameIdPolicy = TRUE, $nameIdValueReq = NULL) {

    $authnRequest = new SamlSPAuthnRequest($this->getSettings(), $forceAuthn, $isPassive, $setNameIdPolicy, $nameIdValueReq);
    $this->_lastRequestID = $authnRequest->getId();

    $samlRequest = $authnRequest->getRequest();
    $parameters['SAMLRequest'] = $samlRequest;
    if (!empty($returnTo)) {
      $parameters['RelayState'] = $returnTo;
    }
    else {
      $parameters['RelayState'] = Utils::getSelfRoutedURLNoQuery();
    }
    $security = $this->getSettings()->getSecurityData();
    if (isset($security['authnRequestsSigned']) && $security['authnRequestsSigned']) {
      $signature = $this->buildRequestSignature($samlRequest, $parameters['RelayState'], $security['signatureAlgorithm']);
      $parameters['SigAlg'] = $security['signatureAlgorithm'];
      $parameters['Signature'] = $signature;
    }

    // Multiple IdPs may be configured, but we can only use one per request.
    // Find the one that should be used for the current login attempt.
    $idp = NULL;
    $idp_data = (object) $this->getSettings()->getIdPData();
    $all_idps = saml_sp__load_all_idps();
    foreach ($all_idps as $this_idp) {
      if ($this_idp->getEntityId() == $idp_data->entityId) {
        $idp = $this_idp;
        break;
      }
    }
    if (!isset($idp)) {
      \Drupal::messenger()->addMessage($this->t('Could not find a valid Identity Provider server.'), MessengerInterface::TYPE_ERROR);
      return $this->redirectTo($parameters['RelayState']);
    }

    // Record the outbound Id of the request.
    $id = $authnRequest->getId();
    saml_sp__track_request($id, $idp, $this->authCallback);
    if (\Drupal::config('saml_sp.settings')->get('debug')) {
      _saml_sp__debug('SAML Request', $samlRequest);
      $decoded_request = base64_decode($samlRequest);
      if ($this->samlSettings->shouldCompressRequests()) {
        $decoded_request = gzinflate($decoded_request);
      }
      _saml_sp__debug('Decoded Request', base64_decode($samlRequest));
      _saml_sp__debug('Parameters', $parameters);

      $url = Url::fromUri($this->getSSOurl(), ['query' => $parameters]);
      return [
        'message' => [
          '#markup' => $this->t('This is a debug page, you can proceed by clicking the following link (this might not work, because "/" chars are encoded differently when the link is made by Drupal as opposed to redirected, as it is when debugging is turned off).') . ' ',
        ],
        'link' => Link::fromTextAndUrl($this->t('test link'), $url)->toRenderable(),
      ];
    }
    return $this->redirectTo($this->getSSOurl(), $parameters);
  }

  /**
   * Builds the request signature.
   */
  public function buildRequestSignature($samlRequest, $relayState, $signAlgorithm = XMLSecurityKey::RSA_SHA1) {
    if (\Drupal::config('saml_sp.settings')->get('debug')) {
      _saml_sp__debug('$this->getSettings()->getSecurityData()', $this->getSettings()->getSecurityData());
    }
    return parent::buildRequestSignature($samlRequest, $relayState, $signAlgorithm);
  }

}
