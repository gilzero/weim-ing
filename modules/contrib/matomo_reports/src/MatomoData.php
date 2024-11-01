<?php

namespace Drupal\matomo_reports;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use GuzzleHttp\Exception\RequestException;

/**
 * Utility class for data retrieved by reports.
 */
class MatomoData {

  /**
   * Return matomo token auth from global or user.
   *
   * @return string
   *   Matomo token auth.
   */
  public static function getToken() {
    $config = \Drupal::config('matomo_reports.matomoreportssettings');
    $current_user = \Drupal::currentUser();
    $user_data = \Drupal::service('user.data')->get('matomo_reports', $current_user->id());
    $user_token = ($current_user->id() && isset($user_data['matomo_reports_token_auth']) ? $user_data['matomo_reports_token_auth'] : '');
    $token_auth = ($config->get('matomo_reports_token_auth') ? $config->get('matomo_reports_token_auth') : $user_token);

    return Html::escape($token_auth);
  }

  /**
   * Return server request results.
   *
   * @param string $query_url
   *   URL of matomo server.
   * @param array $options
   *   Request options to apply. See \GuzzleHttp\RequestOptions.
   * @param string $method
   *   HTTP method to use.
   *
   * @return mixed
   *   JSON decoded server response.
   */
  public static function getResponse($query_url, $options = [], $method = 'GET') {
    $config = \Drupal::config('matomo_reports.matomoreportssettings');
    if ($config->get('matomo_server_url_ignore_ssl')) {
      $options += [
        'curl' => [
          CURLOPT_SSL_VERIFYHOST => FALSE,
          CURLOPT_SSL_VERIFYPEER => FALSE,
        ],
      ];
    }
    try {
      $response = \Drupal::httpClient()->request(strtolower($method), $query_url, $options);
      $data = (string) $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
      else {
        return Json::decode($data);
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Return a list of sites where statistics are accessible on matomo server.
   *
   * @param string $token_auth
   *   Matomo server token auth.
   *
   * @return array|string|bool
   *   Array of sites returned from Matomo reports API.
   */
  public static function getSites($token_auth) {
    $matomo_url = static::getUrl();
    if ($matomo_url) {
      $options = [
        'form_params' => [
          'module' => 'API',
          'method' => 'SitesManager.getSitesWithAtLeastViewAccess',
          'format' => 'JSON',
          'token_auth' => $token_auth,
          'force_api_session' => 1,
        ],
      ];
      return static::getResponse($matomo_url . 'index.php', $options, 'POST');
    }
    else {
      return FALSE;
    }

  }

  /**
   * Return Matomo server url.
   *
   * @return string
   *   Stored value of Matomo server URL.
   */
  public static function getUrl() {
    // Matomo Reports settings takes precedence over Matomo settings.
    $url = \Drupal::config('matomo_reports.matomoreportssettings')->get('matomo_server_url');
    if ($url == '') {
      if (\Drupal::moduleHandler()->moduleExists('matomo')) {
        // Get https url if available first.
        $url = \Drupal::config('matomo.settings')->get('url_http');
        $url = (\Drupal::config('matomo.settings')->get('url_https') ? \Drupal::config('matomo.settings')->get('url_https') : $url);
      }
    }
    if ($url == '') {
      \Drupal::messenger()->addWarning(t('Matomo server url is missing or wrong. Please ask your administrator to check Matomo Reports configuration.'));
    }
    return $url;
  }

}
