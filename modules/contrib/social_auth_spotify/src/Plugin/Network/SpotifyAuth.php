<?php

namespace Drupal\social_auth_spotify\Plugin\Network;

use Drupal\social_auth\Plugin\Network\NetworkBase;

/**
 * Defines a Network Plugin for Social Auth Spotify.
 *
 * @package Drupal\social_auth_spotify\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_spotify",
 *   short_name = "spotify",
 *   social_network = "Spotify",
 *   img_path = "img/spotify-logo.svg",
 *   type = "social_auth",
 *   class_name = "\Kerox\OAuth2\Client\Provider\Spotify",
 *   auth_manager = "\Drupal\social_auth_spotify\SpotifyAuthManager",
 *   routes = {
 *     "redirect": "social_auth.network.redirect",
 *     "callback": "social_auth.network.callback",
 *     "settings_form": "social_auth.network.settings_form",
 *   },
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth\Settings\SettingsBase",
 *       "config_id": "social_auth_spotify.settings"
 *     }
 *   }
 * )
 */
class SpotifyAuth extends NetworkBase {
}
