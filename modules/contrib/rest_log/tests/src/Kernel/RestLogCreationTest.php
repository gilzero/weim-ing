<?php

namespace Drupal\Tests\rest_log\Kernel;

/**
 * Tests rest log creation process.
 *
 * @group rest_log
 */
class RestLogCreationTest extends RestLogKernelTestBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->request = $this->container->get('request_stack')->getCurrentRequest();
  }

  /**
   * Test simple rest log creation by using rest_log_test rest resource.
   */
  public function testCreateRestLog() {
    $number_of_rest_log_entities = $this->getNumberOfRestLogs();
    $this->sendRestRequest('/rest-log-test');
    $this->assertEquals($number_of_rest_log_entities + 1, $this->getNumberOfRestLogs(), 'Rest logs had been created.');
  }

  /**
   * Test "Include same host" module settings form option enabled.
   */
  public function testIncludeSameHostSettingEnabled() {
    $this->configFactory->getEditable('rest_log.settings')
      ->set('include_same_host', TRUE)
      ->save();

    // Send request with the host as a referrer.
    $headers = [
      'Accept' => 'application/json',
      'referer' => $this->request->getSchemeAndHttpHost(),
    ];
    $this->sendRestRequest('/rest-log-test', $headers);

    $this->assertEquals(1, $this->getNumberOfRestLogs(), 'Rest log have been created with host as referrer.');
  }

  /**
   * Test "Include same host" module settings form option disabled.
   */
  public function testIncludeSameHostSettingDisabled() {
    $this->configFactory->getEditable('rest_log.settings')
      ->set('include_same_host', FALSE)
      ->save();

    // Send request with the host as referrer.
    $headers = [
      'Accept' => 'application/json',
      'referer' => $this->request->getSchemeAndHttpHost(),
    ];
    $this->sendRestRequest('/rest-log-test', $headers);
    $this->assertEquals(0, $this->getNumberOfRestLogs(), 'Rest log have not been created with host as referer.');

    // Send request with the other referrer than host.
    $headers = [
      'Accept' => 'application/json',
      'referer' => 'http://www.example.com',
    ];
    $this->sendRestRequest('/rest-log-test', $headers);
    $this->assertEquals(1, $this->getNumberOfRestLogs(), 'Rest log been created with other referer than host.');
  }

}
