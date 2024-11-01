<?php

namespace Drupal\Tests\rest_log\Kernel;

/**
 * Tests rest log automatic cleanup.
 *
 * @group rest_log
 */
class RestLogAutoCleanupTest extends RestLogKernelTestBase {

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->cron = $this->container->get('cron');
  }

  /**
   * Helper function to generate logs.
   *
   * @param int $number_of_logs
   *   Number of logs to be created.
   * @param int $created_time
   *   Created timestamp.
   */
  protected function generateLogs(int $number_of_logs, int $created_time): void {
    for ($i = 0; $i < $number_of_logs; $i++) {
      $this->restLogStorage->create([
        'created' => $created_time,
      ])->save();
    }
  }

  /**
   * Test module auto cleanup.
   */
  public function testAutoCleanup() {
    $module_config = $this->configFactory->getEditable('rest_log.settings');
    $this->assertEquals(2592000, $module_config->get('maximum_lifetime'),
      'Maximum lifetime default value is 30 days.');

    // Disable the cleanup.
    $module_config
      ->set('maximum_lifetime', 0)
      ->save();
    // Create day old logs.
    $this->generateLogs(10, time() - 3600 * 24);
    // Create seven days old logs.
    $this->generateLogs(150, time() - 3600 * 24 * 7);
    // Run the cron to run auto cleanup.
    $this->cron->run();
    $this->assertEquals(160, $this->getNumberOfRestLogs(), 'No rest logs have been removed.');

    // Enable auto cleanup for logs older than 3 days.
    $module_config
      ->set('maximum_lifetime', 3600 * 24 * 3)
      ->save();
    $this->cron->run();
    $this->assertEquals(10, $this->getNumberOfRestLogs(), 'Older logs have been removed.');

    // Run cleanup again.
    $this->cron->run();
    $this->assertEquals(10, $this->getNumberOfRestLogs(), 'No more logs have been removed.');
  }

}
