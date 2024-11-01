<?php

namespace Drupal\push_framework;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\ProcessorInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserDataInterface;

/**
 * Logger service to write messages and exceptions to an external service.
 */
class Service {

  use StringTranslationTrait;

  public const BLOCK_PUSH = 'block push';

  /**
   * The source plugin manager.
   *
   * @var \Drupal\push_framework\SourcePluginManager
   */
  protected SourcePluginManager $pluginManager;

  /**
   * The advanced queue processor.
   *
   * @var \Drupal\advancedqueue\ProcessorInterface
   */
  protected ProcessorInterface $processor;

  /**
   * The queue for push framework.
   *
   * @var \Drupal\advancedqueue\Entity\QueueInterface|null
   */
  protected ?QueueInterface $queue;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The user data storage service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * Constructs the push framework services.
   *
   * @param \Drupal\push_framework\SourcePluginManager $plugin_manager
   *   The source plugin manager.
   * @param \Drupal\advancedqueue\ProcessorInterface $processor
   *   The advanced queue processor.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data storage service.
   */
  public function __construct(SourcePluginManager $plugin_manager, ProcessorInterface $processor, Connection $database, UserDataInterface $user_data) {
    $this->pluginManager = $plugin_manager;
    $this->processor = $processor;
    $this->database = $database;
    $this->userData = $user_data;
  }

  /**
   * Get the queue.
   *
   * @return \Drupal\advancedqueue\Entity\QueueInterface
   *   The queue.
   */
  protected function queue(): QueueInterface {
    if (!isset($this->queue)) {
      $this->queue = Queue::load('push_framework');
      if ($this->queue === NULL) {
        $this->queue = Queue::create([
          'id' => 'push_framework',
          'label' => 'Push Framework',
          'backend' => 'database',
        ]);
        try {
          $this->queue->save();
        }
        catch (EntityStorageException $e) {
          // @todo Log this exception.
        }
      }
    }
    return $this->queue;
  }

  /**
   * Verifies if a given item has already been queued.
   *
   * @param \Drupal\push_framework\SourceItem $item
   *   The item to verify.
   *
   * @return bool
   *   TRUE, if the item has already been queued, FALSE otherwise.
   */
  private function isItemQueued(SourceItem $item): bool {
    $existingItems = $this->database->select('advancedqueue', 'q')
      ->fields('q', ['payload'])
      ->condition('q.queue_id', 'push_framework')
      ->condition('q.type', 'pf_sourceitem')
      ->condition('q.state', 'success', '<>')
      ->condition('q.state', 'failed', '<>')
      ->execute()
      ->fetchCol();
    foreach ($existingItems as $existingItem) {
      /**
       * @var \Drupal\push_framework\SourceItem $sourceItem
       */
      try {
        $sourceItem = SourceItem::fromArray($this->pluginManager, json_decode($existingItem, TRUE, 512, JSON_THROW_ON_ERROR));
        if ($sourceItem->equals($item)) {
          return TRUE;
        }
      }
      catch (PluginException | \JsonException $e) {
        // @todo Log this exception.
      }
    }
    return FALSE;
  }

  /**
   * Get a list of all available source plugins.
   *
   * @return \Drupal\push_framework\SourcePluginInterface[]
   *   The list of available source plugins.
   */
  protected function getPluginInstances(): array {
    $instances = [];
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      try {
        $instances[$id] = $this->pluginManager->createInstance($id);
      }
      catch (PluginException $e) {
        // We can safely ignore this here.
      }
    }
    return $instances;
  }

  /**
   * Collect and queue items from all source plugins.
   */
  public function collectAllSourceItems(): void {
    foreach ($this->getPluginInstances() as $plugin) {
      foreach ($plugin->getAllItemsForPush() as $item) {
        if ($this->userData->get('push_framework', $item->getUid(), self::BLOCK_PUSH)) {
          // No push for this user.
          continue;
        }
        if (!$this->isItemQueued($item)) {
          $job = Job::create('pf_sourceitem', $item->toArray());
          $this->queue()->enqueueJob($job);
        }
      }
    }
  }

  /**
   * Process all items in the advanced queue of the push framework.
   */
  public function processQueue(): void {
    $this->processor->processQueue($this->queue());
  }

}
