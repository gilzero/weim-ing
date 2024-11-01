<?php

namespace Drupal\social_auth_amazon\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth\Settings\SettingsInterface;
use Luchianenco\OAuth2\Client\Provider\Amazon;

/**
 * Defines a Network Plugin for Social Auth Amazon.
 *
 * @package Drupal\social_auth_amazon\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_amazon",
 *   short_name = "amazon",
 *   social_network = "Amazon",
 *   img_path = "img/amazon_logo_rectangular.svg",
 *   type = "social_auth",
 *   class_name = "\Luchianenco\OAuth2\Client\Provider\Amazon",
 *   auth_manager = "\Drupal\social_auth_amazon\AmazonAuthManager",
 *   routes = {
 *     "redirect": "social_auth.network.redirect",
 *     "callback": "social_auth.network.callback",
 *     "settings_form": "social_auth.network.settings_form",
 *   },
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_amazon\Settings\AmazonAuthSettings",
 *       "config_id": "social_auth_amazon.settings"
 *     }
 *   }
 * )
 */
class AmazonAuth extends NetworkBase implements AmazonAuthInterface {

  /**
   * {@inheritdoc}
   */
  protected function initSdk(): mixed {
    $class_name = '\Luchianenco\OAuth2\Client\Provider\Amazon';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Amazon library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_amazon\Settings\AmazonAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId'     => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri'  => $this->getCallbackUrl()->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'] ?? NULL;
      if ($proxyUrl) {
        $league_settings = [
          'proxy' => $proxyUrl,
        ];
      }

      return new Amazon($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth\Settings\SettingsInterface $settings
   *   The Amazon auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(SettingsInterface $settings): bool {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_amazon')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
