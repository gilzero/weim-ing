<?php

namespace Drupal\unstructured;

use Drupal\unstructured\Form\UnstructuredConfigForm;
use Drupal\Core\Config\ConfigFactory;
use Drupal\File\Entity\File;
use Drupal\key\KeyRepository;
use GuzzleHttp\Client;

/**
 * Unstructured API creator.
 */
class UnstructuredApi {

  /**
   * The http client.
   */
  protected Client $client;

  /**
   * The key repository.
   */
  protected KeyRepository $keyRepository;

  /**
   * API Key.
   */
  private string $apiKey;

  /**
   * The base host.
   */
  private string $baseHost = 'https://api.unstructured.io';

  /**
   * Constructs a new Unstructured object.
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
    // Load API key.
    $key = $configFactory->get(UnstructuredConfigForm::CONFIG_NAME)->get('api_key') ?? '';
    if ($key) {
      $this->apiKey = $keyRepository->getKey($key)->getKeyValue();
    }
    $this->baseHost = $configFactory->get(UnstructuredConfigForm::CONFIG_NAME)->get('host') ?? '';
  }

  /**
   * Run a file against the general API.
   *
   * @param File $file
   *   The image url.
   *
   * @return array
   *   The response.
   */
  public function structure($file, $options = []) {
    $guzzleOptions['multipart'] = [
      [
        'name' => 'files',
        'contents' => fopen($file->getFileUri(), 'r'),
        'filename' => $file->getFilename(),
      ],
    ];
    foreach ($options as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $subValue) {
          $guzzleOptions['multipart'][] = [
            'name' => $key,
            'contents' => $subValue,
          ];
        }
      }
      else {
        $guzzleOptions['multipart'][] = [
          'name' => $key,
          'contents' => $value,
        ];
      }
    }
    $result = json_decode($this->makeRequest("general/v0/general", [], 'POST', NULL, $guzzleOptions), TRUE);

    return $result;
  }

  /**
   * Make Unstructured call.
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
    if (!$this->baseHost) {
      throw new \Exception('No base host set.');
    }
    // We can wait long time since its expensive API calls.
    $options['connect_timeout'] = 600;
    $options['read_timeout'] = 600;
    $options['timeout'] = 600;
    // Basic auth.
    if ($this->apiKey) {
      $options['headers']['unstructured-api-key'] = $this->apiKey;
    }
    if ($body) {
      $options['body'] = $body;
    }

    $new_url = rtrim($this->baseHost, '/') . '/' . $path;
    $new_url .= count($query_string) ? '?' . http_build_query($query_string) : '';

    $res = $this->client->request($method, $new_url, $options);

    return $res->getBody();
  }

}
