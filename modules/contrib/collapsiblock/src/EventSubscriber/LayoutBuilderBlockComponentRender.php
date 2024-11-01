<?php

namespace Drupal\collapsiblock\EventSubscriber;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event handler to alter how Layout Builder blocks are rendered.
 */
class LayoutBuilderBlockComponentRender implements EventSubscriberInterface {

  /**
   * A config object for the collapsiblock configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $collapsibleBlockConfig;

  /**
   * Constructs the layout builder block event subscriber.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->collapsibleBlockConfig = $config_factory->get('collapsiblock.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Attach Collapsiblock settings and libraries to layout builder components
    // when a layout builder Section Component's render array is being built.
    //
    // Note that 'section_component.build.render_array' is the value of
    // \Drupal\layout_builder\LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY,
    // but we can't use the constant directly, because it does not resolve when
    // the layout_builder module is disabled.
    $events = [];
    $events['section_component.build.render_array'] = 'attachCollapsiblock';

    return $events;
  }

  /**
   * Attach Collapsiblock settings and libraries to layout builder components.
   *
   * This acts when a layout builder Section Component's render array is being
   * built.
   *
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   An event object containing context about the Section Component and the
   *   render array being built. Should be an instance of
   *   \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent,
   *   otherwise, no actions will be performed.
   *
   * @see \collapsiblock_block_view_alter()
   * @see \Drupal\layout_builder\LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY
   * @see getSubscribedEvents()
   */
  public function attachCollapsiblock(Event $event) {
    if (!($event instanceof SectionComponentBuildRenderArrayEvent)) {
      return;
    }

    $build = $event->getBuild();
    $current_lb_component = $event->getComponent();
    $saved_settings = $current_lb_component->get('additional');
    $saved_settings += [
      'collapsiblock' => [
        'collapse_action' => 0,
      ],
    ];

    if (empty($build['#configuration']['id'])) {
      return;
    }

    // If the action is anything other than 'none', create our wrapper
    // elements.
    $collapse_action = $saved_settings['collapsiblock']['collapse_action'];
    if ($collapse_action == 0) {
      $collapse_action = $this->collapsibleBlockConfig->get('default_action');
    }

    if ($collapse_action != 1) {
      // Generate a valid HTML ID.
      $id = Html::getId('collapsiblock-wrapper-' . $build['#configuration']['id'] . '-' . $current_lb_component->getUuid());
      $classes = [];
      $classes[] = 'collapsiblockTitle';

      $build['#collapsiblock']['prefix'] = [
        '#markup' => sprintf('<div id="%s" class="%s" data-collapsiblock-action="%s">', $id, implode(' ', $classes), $collapse_action),
      ];
      $build['#collapsiblock']['suffix'] = [
        'collapsiblock' => [
          '#markup' => '</div>',
        ],
      ];
      $event->setBuild($build);
    }
    $event->addCacheableDependency($this->collapsibleBlockConfig);
  }

}
