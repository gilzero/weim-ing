<?php

namespace Drupal\push_framework;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Source item class.
 */
final class SourceItem {

  /**
   * The oid.
   *
   * @var string
   */
  protected string $oid;

  /**
   * The uid.
   *
   * @var int
   */
  protected int $uid;

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User|null
   */
  protected ?User $user;

  /**
   * The source plugin.
   *
   * @var \Drupal\push_framework\SourcePluginInterface
   */
  protected SourcePluginInterface $plugin;

  /**
   * Whether the item is initialized or not.
   *
   * @var bool
   */
  protected bool $initialized;

  /**
   * The tasks.
   *
   * @var array|null
   */
  protected ?array $tasks = NULL;

  /**
   * SourceItem constructor.
   *
   * @param \Drupal\push_framework\SourcePluginInterface $plugin
   *   The source plugin.
   * @param string $oid
   *   The ID of the object.
   * @param int $uid
   *   The user ID of the recipient.
   */
  public function __construct(SourcePluginInterface $plugin, string $oid, int $uid) {
    $this->plugin = $plugin;
    $this->oid = $oid;
    $this->uid = $uid;
    $this->initialized = FALSE;
  }

  /**
   * Compares with the given SourceItem.
   *
   * @param \Drupal\push_framework\SourceItem $item
   *   The source item.
   *
   * @return bool
   *   Return TRUE, if the source item has the same ID and recipient, FALSE
   *   otherwise.
   */
  public function equals(SourceItem $item): bool {
    return $item->oid === $this->oid && $item->uid === $this->uid;
  }

  /**
   * Gets the user ID.
   *
   * @return int
   *   The user ID.
   */
  public function getUid(): int {
    return $this->uid;
  }

  /**
   * Loads and gets the users.
   *
   * @return \Drupal\user\UserInterface
   *   The recipient's user account.
   */
  protected function user(): UserInterface {
    if (!isset($this->user)) {
      $this->user = User::load($this->uid);
    }
    return $this->user;
  }

  /**
   * Determine and sets the tasks that need to be done.
   *
   * @param \Drupal\push_framework\ChannelPluginManager $channel_plugin_manager
   *   The channel plugin manager.
   */
  private function determineAndSetTasks(ChannelPluginManager $channel_plugin_manager): void {
    if ($this->tasks === NULL) {
      $this->tasks = [];
      foreach ($channel_plugin_manager->getDefinitions() as $id => $ids) {
        try {
          /**
           * @var \Drupal\push_framework\ChannelPluginInterface $plugin
           */
          $plugin = $channel_plugin_manager->createInstance($id);
          if ($plugin->applicable($this->user())) {
            // @todo Allow users to define their own preferences.
            $this->tasks[] = [
              'channel plugin id' => $plugin->getPluginId(),
              'attempt' => 0,
              'mute subsequent until completed' => TRUE,
              'skip subsequent on success' => TRUE,
            ];
          }
        }
        catch (PluginException $e) {
          // @todo Log this exception.
        }
      }
    }
  }

  /**
   * Process the tasks.
   *
   * @param ChannelPluginManager $channel_plugin_manager
   *   The channel plugin manager.
   *
   * @return bool
   *   TRUE, if all pending tasks have been processed, FALSE otherwise.
   */
  public function process(ChannelPluginManager $channel_plugin_manager): bool {
    if (!$this->initialized) {
      $this->determineAndSetTasks($channel_plugin_manager);
      $this->initialized = TRUE;
    }

    $remainingTasks = [];
    $mute = FALSE;
    foreach ($this->tasks as $task) {
      try {
        /**
         * @var \Drupal\push_framework\ChannelPluginInterface $channelPlugin
         */
        $channelPlugin = $channel_plugin_manager->createInstance($task['channel plugin id']);
      }
      catch (PluginException $e) {
        // @todo Log this exception.
        continue;
      }
      if (!$mute) {
        // Use channel plugin and deliver message.
        $task['attempt']++;
        if ($channelPlugin->isActive() && $entity = $this->plugin->getObjectAsEntity($this->oid)) {
          $content = $channelPlugin->prepareContent($this->user(), $entity, $this->plugin, $this->oid);
          $result = $channelPlugin->send($this->user(), $entity, $content, $task['attempt']);
        }
        else {
          $result = ChannelPluginInterface::RESULT_STATUS_FAILED;
        }
        // Send feedback to the source plugin about the tasks and its result.
        $this->plugin->confirmAttempt($this->oid, $this->user(), $channelPlugin, $result);

        $mute = $task['mute subsequent until completed'] ?? FALSE;
      }
      else {
        $result = ChannelPluginInterface::RESULT_STATUS_RETRY;
      }
      if ($result === ChannelPluginInterface::RESULT_STATUS_RETRY) {
        // Remember this task for a later retry.
        $remainingTasks[] = $task;
      }
      elseif ($result === ChannelPluginInterface::RESULT_STATUS_SUCCESS) {
        if ($task['skip subsequent on success'] ?? FALSE) {
          break;
        }
      }
    }
    $this->tasks = $remainingTasks;

    if (empty($this->tasks)) {
      $this->plugin->confirmDelivery($this->oid, $this->user());
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Creates a SourceItem by array.
   *
   * @param \Drupal\push_framework\SourcePluginManager $plugin_manager
   *   The source plugin manager.
   * @param array $payload
   *   The payload from which to instantiate the source item.
   *
   * @return \Drupal\push_framework\SourceItem
   *   The source item.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function fromArray(SourcePluginManager $plugin_manager, array $payload): SourceItem {
    /**
     * @var \Drupal\push_framework\SourcePluginInterface $plugin
     */
    $plugin = $plugin_manager->createInstance($payload['plugin id']);
    $item = new SourceItem($plugin, $payload['oid'], $payload['uid']);
    $item->initialized = $payload['initialized'];
    $item->tasks = $payload['tasks'];
    return $item;
  }

  /**
   * Creates an array based on members.
   *
   * @return array
   *   The array representing this source item.
   */
  public function toArray(): array {
    return [
      'oid' => $this->oid,
      'uid' => $this->uid,
      'plugin id' => $this->plugin->getPluginId(),
      'initialized' => $this->initialized,
      'tasks' => $this->tasks,
    ];
  }

}
