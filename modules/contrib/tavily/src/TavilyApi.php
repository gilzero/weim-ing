<?php

namespace Drupal\tavily;

use Drupal\tavily\Form\TavilyConfigForm;
use Drupal\Core\Config\ConfigFactory;
use Drupal\key\KeyRepository;
use GuzzleHttp\Client;

/**
 * Tavily API creator.
 */
class TavilyApi {

  /**
   * The http client.
   */
  protected Client $client;

  /**
   * API Key.
   */
  private string $apiKey;

  /**
   * The base host.
   */
  private string $baseHost = 'https://api.tavily.com/';

  /**
   * Constructs a new Tavily object.
   *
   * @param \GuzzleHttp\Client $client
   *   Http client.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\key\KeyRepository $keyRepository
   *   The key repository.
   */
  public function __construct(Client $client, ConfigFactory $configFactory, KeyRepository $keyRepository) {
    $this->client = $client;
    $key = $configFactory->get(TavilyConfigForm::CONFIG_NAME)->get('api_key') ?? '';
    if ($key) {
      $this->apiKey = $keyRepository->getKey($key)->getKeyValue();
    }
  }

  /**
   * Search for a word in Tavily.
   *
   * @param string $searchWord
   *   The search word.
   * @param array $options
   *   Extra options.
   *
   * @return array
   *   The response.
   */
  public function search($searchWord, array $options = []) {
    $query = $options;
    $query['query'] = $searchWord;
    $result = json_decode($this->makeRequest("search", [], 'POST', $query), TRUE);
    return $result;
  }

  /**
   * Make tavily call.
   *
   * @param string $path
   *   The path.
   * @param array $query_string
   *   The query string.
   * @param string $method
   *   The method.
   * @param string $body
   *   Data to attach if POST/PUT/PATCH.
   * @param array $options
   *   Extra headers.
   *
   * @return string|object
   *   The return response.
   */
  protected function makeRequest($path, array $query_string = [], $method = 'GET', $body = '', array $options = []) {
    if (!$this->apiKey) {
      throw new \Exception('No api key set.');
    }
    // Don't wait to long.
    $options['connect_timeout'] = 30;
    $options['read_timeout'] = 30;
    $options['timeout'] = 30;

    // Credentials
    $body['api_key'] = $this->apiKey;
    if ($body) {
      $options['body'] = json_encode($body);
    }

    $new_url = rtrim($this->baseHost, '/') . '/' . $path;
    $new_url .= count($query_string) ? '?' . http_build_query($query_string) : '';

    $res = $this->client->request($method, $new_url, $options);

    return $res->getBody();
  }

}
