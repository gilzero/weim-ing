<?php

namespace Drupal\social_auth_amazon\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Amazon settings.
 */
class AmazonAuthSettings extends SettingsBase implements AmazonAuthSettingsInterface {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Restricted domain.
   *
   * @var string
   */
  protected $restrictedDomain;

  /**
   * {@inheritdoc}
   */
  public function getClientId(): ?string {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret(): ?string {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }
    return $this->clientSecret;
  }

}
