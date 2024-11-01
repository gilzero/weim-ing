<?php

namespace Drupal\google_places;

use Drupal\google_places\Form\GooglePlacesConfigForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Client;

/**
 * Google Places API creator.
 */
class GooglePlacesApi {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * API Key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * The base path.
   *
   * @var string
   */
  private $oldApiBasePath = 'https://maps.googleapis.com/maps/api/place/';

  /**
   * The base path.
   *
   * @var string
   */
  private $newApiBasePath = 'https://places.googleapis.com/v1/';

  /**
   * Constructs a new Google Places object.
   *
   * @param \GuzzleHttp\Client $client
   *   Http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository.
   */
  public function __construct(Client $client, ConfigFactoryInterface $configFactory, KeyRepositoryInterface $keyRepository) {
    $this->client = $client;
    $key = $configFactory->get(GooglePlacesConfigForm::CONFIG_NAME)->get('api_key') ?? '';
    if ($key) {
      $this->apiKey = $keyRepository->getKey($key)->getKeyValue();
    }
  }

  /**
   * Gets an places object (legacy).
   *
   * @param string $search
   *   The address to search for.
   *
   * @return array
   *   All Google info.
   */
  public function getPlaceInfo($search) {
    if (!$this->apiKey) {
      return [];
    }
    $headers['accept'] = 'application/json';
    $headers['Content-Type'] = 'application/json';
    $qs['input'] = $search;
    $qs['inputtype'] = 'textquery';
    $candidates = json_decode($this->makeRequest('findplacefromtext/json', $qs, 'GET', NULL, $headers)->getBody(), TRUE);
    if (!empty($candidates['candidates'][0]['place_id'])) {
      $qs = [
        'place_id' => $candidates['candidates'][0]['place_id'],
      ];
      $response = json_decode($this->makeRequest('details/json', $qs, 'GET', NULL, $headers)->getBody(), TRUE);
      return $response;
    }
    return [];
  }

  /**
   * Places API.
   *
   * @param string $search
   *   The address to search for.
   * @param string $fieldMask
   *   A custom field mask.
   *
   * @return array
   *   All Google info.
   */
  public function placesSearchApi($search, $fieldMask = '') {
    if (!$this->apiKey) {
      return [];
    }
    $data['textQuery'] = $search;
    $headers['accept'] = 'application/json';
    $headers['Content-Type'] = 'application/json';
    $headers['X-Goog-FieldMask'] = $fieldMask ?? 'places.id';
    $response = $this->makeRequest('places:searchText', [], 'POST', $data, $headers, 'new')->getBody();
    return json_decode($response, TRUE);
  }

  /**
   * Places Details API.
   *
   * @param string $id
   *   The id.
   * @param string $fieldMask
   *   The field mask.
   *
   * @return array
   *   All Google info.
   */
  public function placesDetailsApi($id, $fieldMask = '*') {
    if (!$this->apiKey) {
      return [];
    }
    $headers['accept'] = 'application/json';
    $headers['Content-Type'] = 'application/json';
    $headers['X-Goog-FieldMask'] = $fieldMask;
    $response = $this->makeRequest('places/' . $id, [], 'GET', [], $headers, 'new')->getBody();
    return json_decode($response->getContents(), TRUE);
  }

  /**
   * Get a photo.
   *
   * @param string $prefix
   *   The prefix.
   * @param array $params
   *   The params.
   *
   * @return string
   *   Photo binary.
   */
  public function getPhoto($prefix, $params = []) {
    if (!$this->apiKey) {
      return [];
    }
    $path = $prefix . '/media';
    $res = $this->makeRequest($path, $params, 'GET', [], [], 'new');
    return $res->getBody()->getContents();
  }

  /**
   * Make google call.
   *
   * @param string $path
   *   The path.
   * @param array $query_string
   *   The query string.
   * @param string $method
   *   The method.
   * @param string $body
   *   Data to attach if POST/PUT/PATCH.
   * @param array $headers
   *   Extra headers.
   * @param string $api
   *   The API to use.
   *
   * @return \Guzzle\Http\Message\Response
   *   The return response.
   */
  protected function makeRequest($path, array $query_string = [], $method = 'GET', $body = '', array $headers = [], $api = 'old') {
    // We can't wait forever.
    $options['connect_timeout'] = 10;
    $options['read_timeout'] = 10;

    // Don't let Guzzle die, just forward body and status.
    $options['http_errors'] = FALSE;
    // Headers.
    $options['headers'] = $headers;
    // API key.
    if ($api == 'old') {
      $query_string['key'] = $this->apiKey;
    }
    else {
      $options['headers']['X-Goog-Api-Key'] = $this->apiKey;
    }

    if ($body) {
      $options['json'] = $body;
    }

    $new_url = $api == 'old' ? $this->oldApiBasePath : $this->newApiBasePath;
    $new_url .= $path;
    $new_url .= count($query_string) ? '?' . http_build_query($query_string) : '';

    $res = $this->client->request($method, $new_url, $options);

    return $res;
  }
}
