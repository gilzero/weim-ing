<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Plugin\PushFrameworkChannel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\pf_notifications\Service\PushInterface;
use Drupal\pf_notifications\Service\SubscriptionInterface;
use Drupal\push_framework\ChannelBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the push framework channel.
 *
 * @ChannelPlugin(
 *   id = "pf_notifications",
 *   label = @Translation("Push framework notifications"),
 *   description = @Translation("Provides push notifications channel plugin.")
 * )
 */
class PushNotification extends ChannelBase {

  use StringTranslationTrait;

  /**
   * Push notifications push service.
   *
   * @var \Drupal\pf_notifications\Service\BaseInterface
   */
  protected BaseInterface $service;

  /**
   * Push notifications push service.
   *
   * @var \Drupal\pf_notifications\Service\PushInterface
   */
  protected PushInterface $push;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->service = $container->get('pf_notifications.base');
    $instance->push = $container->get('pf_notifications.push');
    $instance->configFactory = $container->get('config.factory');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName(): string {
    return 'pf_notifications.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function applicable(UserInterface $user): bool {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function send(UserInterface $user, ContentEntityInterface $entity, array $content, int $attempt): string {

    $uid = (int) $user->id();
    $name = $user->getDisplayName() ?: $user->getAccountName();
    $entity_data = $this->service->getEntityData($uid, $name, $entity);
    $entity_links = $this->service->entityLinks($entity_data);

    $langcode = $entity->language()->getId() ?: $user->getPreferredLangcode();
    $title_label = $entity_data['parent_label'] ?? $entity->label();
    $title = $content[$langcode]['subject'] ?: ($content['subject'] ?: $title_label);
    $body = $content[$langcode]['body'] ?: ($content['body'] ?: SubscriptionInterface::SUBSCRIBE_BODY);

    $url = $entity_links['entity']->getUrl();

    $push_data = [
      'content' => [
        'title' => Html::decodeEntities($title),
        'body' => Html::decodeEntities($body),
        'icon' => $this->service->getConfig()->get('icon') ?? '',
        'url' => $url->toString(),
      ],
      'options' => $this->push->defaultOptions(),
      'entity_data' => $entity_data,
      'entity_links' => $entity_links,
    ];

    try {

      // Allow other modules to alter notification data.
      $this->moduleHandler->invokeAll('pf_notifications_push_data', [&$push_data, $entity, $entity_data]);

      // Use WebPush class to queue the notification.
      $this->push->sendNotification($push_data);
      return self::RESULT_STATUS_SUCCESS;
    }
    catch (\Exception $e) {
      $error = self::RESULT_STATUS_FAILED . ': ' . $e->getMessage();
      $this->service->getLogger()->error($error);
      return $error;
    }
  }

}
