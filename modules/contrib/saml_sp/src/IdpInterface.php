<?php

namespace Drupal\saml_sp;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface IdpInterface extends ConfigEntityInterface {

  /**
   * Returns the application name provided to the IdP server.
   *
   * @return string
   *   The application name.
   */
  public function getAppName();

  /**
   * Returns the authentication methods requested for this IdP.
   *
   * @return array
   *   The authentication methods.
   */
  public function getAuthnContextClassRef();

  /**
   * Returns the entity ID of the IdP server.
   *
   * @return string
   *   The IdP server's entity ID.
   */
  public function getEntityId();

  /**
   * Returns the IdP server's login URL.
   *
   * @return string
   *   The login URL.
   */
  public function getLoginUrl();

  /**
   * Returns the IdP server's login URL.
   *
   * @return string
   *   The logout URL.
   */
  public function getLogoutUrl();

  /**
   * Returns the response field used to uniquely identify a user.
   *
   * @return string
   *   The field name.
   */
  public function getNameIdField();

  /**
   * Returns the X.509 certificates for the IdP server.
   *
   * @return string
   *   The certificates.
   */
  public function getX509Cert();

  /**
   * Sets the application name.
   *
   * @param string $app_name
   *   The application name.
   */
  public function setAppName($app_name);

  /**
   * Sets the authentication methods requested for this IdP.
   *
   * @param array $authn_context_class_ref
   *   The authentication methods.
   */
  public function setAuthnContextClassRef(array $authn_context_class_ref);

  /**
   * Sets the entity ID of the IdP server.
   *
   * @param string $entity_id
   *   The IdP server's entity ID.
   */
  public function setEntityId($entity_id);

  /**
   * Sets the IdP server's login URL.
   *
   * @param string $login_url
   *   The login URL.
   */
  public function setLoginUrl($login_url);

  /**
   * Sets the IdP server's logout URL.
   *
   * @param string $logout_url
   *   The logout URL.
   */
  public function setLogoutUrl($logout_url);

  /**
   * Sets the response field used to uniquely identify a user.
   *
   * @param string $nameid_field
   *   The field name.
   */
  public function setNameIdField($nameid_field);

  /**
   * Sets the X.509 certificates for the IdP server.
   *
   * @param array $x509_certs
   *   The certificates.
   */
  public function setX509Cert(array $x509_certs);

}
