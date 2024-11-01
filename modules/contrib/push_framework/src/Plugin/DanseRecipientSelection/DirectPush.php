<?php

namespace Drupal\push_framework\Plugin\DanseRecipientSelection;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\danse\PayloadInterface;
use Drupal\danse\RecipientSelectionBase;
use Drupal\push_framework\ChannelPluginInterface;
use Drupal\push_framework\ChannelPluginManager;
use Drupal\push_framework\SourcePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of DANSE recipient selection.
 */
abstract class DirectPush extends RecipientSelectionBase implements DirectPushInterface {

  /**
   * The channel plugin manager.
   *
   * @var \Drupal\push_framework\ChannelPluginManager
   */
  protected ChannelPluginManager $channelPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->channelPluginManager = $container->get('push_framework.channel.plugin.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  final public function getRecipients(PayloadInterface $payload): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function push(PayloadInterface $payload, SourcePluginInterface $plugin = NULL, string $oid = NULL): string {
    try {
      /**
       * @var \Drupal\push_framework\ChannelPluginInterface $channel
       */
      $channel = $this->channelPluginManager->createInstance($this->directPushChannelId());
      /**
       * @var \Drupal\user\UserInterface $user
       */
      $user = $this->entityTypeManager->getStorage('user')->load(1);
    }
    catch (PluginException $e) {
      // @todo Log this exception.
      return ChannelPluginInterface::RESULT_STATUS_FAILED;
    }
    if ($channel->isActive()) {
      $entity = $payload->getEntity();
      $content = $channel->prepareContent($user, $entity, $plugin, $oid);
      $result = $channel->send($user, $entity, $content, 0);
    }
    else {
      $result = ChannelPluginInterface::RESULT_STATUS_FAILED;
    }
    return $result;
  }

}
