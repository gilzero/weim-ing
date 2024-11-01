<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Functional;

use Drupal\Core\Database\Connection;

/**
 * Tests posting to statistics.php with invalid values.
 *
 * @group statistics
 */
class StatisticsInvalidPostTest extends StatisticsTestBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected ?Connection $db;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The front controller path.
   *
   * @var string
   */
  protected string $postUrl;

  /**
   * Manually calling statistics.php.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function post(mixed $nid): void {
    $client = $this->getHttpClient();
    $client->post($this->postUrl, ['form_params' => ['nid' => $nid]]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->db = $this->container
      ->get('database');
    $this->postUrl = $this->buildUrl(
      $this->container
        ->get('extension.list.module')
        ->getPath('statistics') . '/statistics.php'
    );
  }

  /**
   * Test if nothing breaks when posting with invalid params.
   *
   * @dataProvider Drupal\Tests\statistics\Unit\StatisticsNidFilterTest::providerTestNidFilter()
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testInvalidPost(mixed $nid, mixed $_, mixed $expected): void {
    $this->post($nid);

    $stmt = $this->db->select('node_counter', 'n')
      ->fields('n', ['nid'])
      ->condition('n.nid', $nid)
      ->execute();
    $this->assertNotNull($stmt);
    $record = $stmt->fetchField(0);
    if ($expected === FALSE) {
      $this->assertFalse($record, 'Verifying that nothing is written to the node_counter table.');
      return;
    }
    $this->assertSame($expected, intval($record));
  }

}
