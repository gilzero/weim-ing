<?php

namespace Drupal\eca_push_framework\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\push_framework\ChannelEvents;
use Drupal\push_framework\Event\ChannelEventBase;
use Drupal\push_framework\Event\ChannelEventLanguageBase;
use Drupal\push_framework\Event\ChannelPostRender;
use Drupal\push_framework\Event\ChannelPreBuild;
use Drupal\push_framework\Event\ChannelPrepareTemplates;
use Drupal\push_framework\Event\ChannelPreRender;

/**
 * Plugin implementation of the ECA Events for the Push Framework.
 *
 * @EcaEvent(
 *   id = "push_framework_channel",
 *   deriver = "Drupal\eca_push_framework\Plugin\ECA\Event\ChannelEventDeriver",
 *   eca_version_introduced = "2.3.0"
 * )
 */
class ChannelEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $events = [];
    $events['prepare_templates'] = [
      'label' => 'Prepare templates',
      'event_name' => ChannelEvents::PREPARE_TEMPLATES,
      'event_class' => ChannelPrepareTemplates::class,
    ];
    $events['pre_build'] = [
      'label' => 'Pre build notification',
      'event_name' => ChannelEvents::PRE_BUILD,
      'event_class' => ChannelPreBuild::class,
    ];
    $events['pre_render'] = [
      'label' => 'Pre render notification',
      'event_name' => ChannelEvents::PRE_RENDER,
      'event_class' => ChannelPreRender::class,
    ];
    $events['post_render'] = [
      'label' => 'Post render notification',
      'event_name' => ChannelEvents::POST_RENDER,
      'event_class' => ChannelPostRender::class,
    ];
    return $events;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  #[Token(
    name: 'push_notification',
    description: 'The push notification.',
    classes: [
      ChannelEventBase::class,
    ],
    properties: [
      new Token(
        name: 'channel',
        description: 'The notification channel.',
      ),
      new Token(
        name: 'recipient',
        description: 'The recipient user account.',
      ),
      new Token(
        name: 'entity',
        description: 'The entity.',
      ),
      new Token(
        name: 'title',
        description: 'The title of the entity.',
      ),
      new Token(
        name: 'display_mode',
        description: 'The display mode for the entity.',
      ),
      new Token(
        name: 'subject',
        description: 'The subject of the notification.',
        classes: [
          ChannelPrepareTemplates::class,
        ],
      ),
      new Token(
        name: 'body',
        description: 'The body of the notification.',
        classes: [
          ChannelPrepareTemplates::class,
        ],
      ),
      new Token(
        name: 'text_format',
        description: 'The text format of the notification.',
        classes: [
          ChannelPrepareTemplates::class,
        ],
      ),
      new Token(
        name: 'is_html',
        description: 'The flag if the body of the notification is in html.',
        classes: [
          ChannelPrepareTemplates::class,
        ],
      ),
      new Token(
        name: 'language_key',
        description: 'The language key of the notification.',
        classes: [
          ChannelEventLanguageBase::class,
        ],
      ),
      new Token(
        name: 'elements',
        description: 'The list of elements.',
        classes: [
          ChannelPreRender::class,
        ],
      ),
      new Token(
        name: 'output',
        description: 'The rendered output of the notification.',
        classes: [
          ChannelPostRender::class,
        ],
      ),
    ],
  )]
  public function getData(string $key): mixed {
    $event = $this->getEvent();

    if ($key === 'push_notification' && $event instanceof ChannelEventBase) {
      $data = DataTransferObject::create();
      $data->set('channel',
        DataTransferObject::create($event->getChannelPlugin()->getPluginId()));
      $data->set('recipient', DataTransferObject::create($event->getUser()));
      $data->set('entity', DataTransferObject::create($event->getEntity()));
      $data->set('display_mode', DataTransferObject::create($event->getDisplayMode()));
      if ($event instanceof ChannelPrepareTemplates) {
        if ($event->getEntity()->getFieldDefinition('title')) {
          $data->set('title',
            DataTransferObject::create($event->getEntity()->get('title')->value));
        }
        else {
          $data->set('title', '');
        }
        $data->set('subject', DataTransferObject::create($event->getSubject()));
        $data->set('body', DataTransferObject::create($event->getBody()));
        $data->set('text_format', DataTransferObject::create($event->getTextFormat()));
        $data->set('is_html', DataTransferObject::create($event->isHtml()));
      }
      if ($event instanceof ChannelEventLanguageBase) {
        $data->set('language_key', DataTransferObject::create($event->getLanguageKey()));
      }
      if ($event instanceof ChannelPreRender) {
        $data->set('elements', DataTransferObject::create($event->getElements()));
      }
      if ($event instanceof ChannelPostRender) {
        $data->set('output', DataTransferObject::create($event->getOutput()));
      }
      return $data;
    }

    return parent::getData($key);
  }

}
