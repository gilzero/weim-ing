<?php

namespace Drupal\saml_sp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\saml_sp\IdpInterface;

/**
 * Defines the Idp entity.
 *
 * @ConfigEntityType(
 *   id = "idp",
 *   label = @Translation("Identity Provider"),
 *   handlers = {
 *     "list_builder" = "Drupal\saml_sp\Controller\IdpListBuilder",
 *     "form" = {
 *       "add" = "Drupal\saml_sp\Form\IdpForm",
 *       "edit" = "Drupal\saml_sp\Form\IdpForm",
 *       "delete" = "Drupal\saml_sp\Form\IdpDeleteForm",
 *     }
 *   },
 *   config_prefix = "idp",
 *   admin_permission = "configure saml sp",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/saml_sp/idp/edit/{idp}",
 *     "delete-form" = "/admin/config/people/saml_sp/idp/delete/{idp}",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_id",
 *     "app_name",
 *     "nameid_field",
 *     "login_url",
 *     "logout_url",
 *     "x509_cert",
 *     "authn_context_class_ref",
 *   }
 * )
 */
class Idp extends ConfigEntityBase implements IdpInterface {

  /**
   * The machine name of the IdP entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the IdP entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The name of this application provided to the Identity Provider server.
   *
   * @var string
   */
  protected $app_name;

  /**
   * Authentication methods used by the Identity Provider server.
   *
   * @var array
   */
  protected $authn_context_class_ref;

  /**
   * The entityID that the Identity Provider server will use to identiy itself.
   *
   * @var string
   */
  protected $entity_id;

  /**
   * Login URL of the Identity Provider server.
   *
   * @var string
   */
  protected $login_url;

  /**
   * Logout URL of the Identity Provider server.
   *
   * @var string
   */
  protected $logout_url;

  /**
   * The field used to uniquely identify users.
   *
   * @var string
   */
  protected $nameid_field;

  /**
   * X.509 certificate of the Identity Provider server.
   *
   * @var array
   */
  protected $x509_cert;

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public function __construct(array $values = [], $entity_type = 'idp') {
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getAppName() {
    return $this->app_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthnContextClassRef() {
    return is_array($this->authn_context_class_ref) ? $this->authn_context_class_ref : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoginUrl() {
    return $this->login_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoutUrl() {
    return $this->logout_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getNameIdField() {
    return $this->nameid_field;
  }

  /**
   * {@inheritdoc}
   */
  public function getX509Cert() {
    return $this->x509_cert;
  }

  /**
   * {@inheritdoc}
   */
  public function setAppName($app_name) {
    $this->app_name = $app_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthnContextClassRef(array $authn_context_class_refs) {
    $array = [];
    foreach ($authn_context_class_refs as $value) {
      if ($value) {
        $array[$value] = $value;
      }
    }
    $this->authn_context_class_ref = $array;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityId($entity_id) {
    $this->entity_id = $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setLoginUrl($login_url) {
    $this->login_url = $login_url;
  }

  /**
   * {@inheritdoc}
   */
  public function setLogoutUrl($logout_url) {
    $this->logout_url = $logout_url;
  }

  /**
   * {@inheritdoc}
   */
  public function setNameIdField($nameid_field) {
    $this->nameid_field = $nameid_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setX509Cert(array $x509_certs) {
    if (isset($x509_certs['actions'])) {
      unset($x509_certs['actions']);
    }
    $this->x509_cert = $x509_certs;
  }

}
