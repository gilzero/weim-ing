<?php

namespace Drupal\views_show_more\EventSubscriber;

use Drupal\Core\Render\RendererInterface;
use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class ShowMoreEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Render\RendererInterface definition.
   */
  protected RendererInterface $renderer;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    // Only alter views ajax responses.
    if (!($response instanceof ViewAjaxResponse)) {
      return;
    }

    $view = $response->getView();
    // Only alter commands if the user has selected our pager, and it's
    // attempting to move beyond page 0.
    if ($view->getPager()->getPluginId() !== 'show_more' || $view->getCurrentPage() === 0) {
      return;
    }

    $commands = &$response->getCommands();
    foreach ($commands as $key => $command) {
      // Remove "viewsScrollTop" command, not needed.
      if (in_array($command['command'], ['scrollTop', 'viewsScrollTop'])) {
        unset($commands[$key]);
      }

      // The replacement should the only one, but just in case, we'll make sure.
      if ($command['command'] === 'insert' && $command['method'] === 'replaceWith') {
        $style_plugin = $view->style_plugin->getPluginDefinition()['id'];
        $options = &$view->style_plugin->options;
        $pager_options = $view->pager->options;

        if ($style_plugin === 'html_list' &&
        in_array($options['type'], ['ul', 'ol'])) {
          $target = "> {$options['type']}";
          if (!empty($options['wrapper_class'])) {
            $wrapper_classes = str_replace(' ', '.', $options['wrapper_class']);
            $target = ".{$wrapper_classes} {$target}";
          }
          $commands[$key]['append_at'] = $target;
        }
        elseif ($style_plugin === 'table') {
          $commands[$key]['append_at'] = '.views-table tbody';
        }
        elseif ($style_plugin === 'grid') {
          $commands[$key]['append_at'] = '.views-view-grid';
        }

        $commands[$key]['command'] = 'viewsShowMore';
        $commands[$key]['method'] = $pager_options['result_display_method'];
        if (isset($pager_options['effects']) && $pager_options['effects']['type'] !== 'none') {
          $commands[$key]['effect'] = $pager_options['effects']['type'];
          $commands[$key]['speed'] = $pager_options['effects']['speed'];
          $commands[$key]['scroll_offset'] = $pager_options['effects']['scroll_offset'];
        }
        $commands[$key]['options'] = [
          'content_selector' => $pager_options['advance']['content_selector'],
          'pager_selector' => $pager_options['advance']['pager_selector'],
          'header_selector' => $pager_options['advance']['header_selector'],
          'footer_selector' => $pager_options['advance']['footer_selector'],
        ];
      }

    }

    // Update header & footer.
    $attachments = $response->getAttachments();
    foreach ($view->header as $header) {
      $header_render = $header->render();
      $attachments['drupalSettings']['header'] = $attachments['drupalSettings']['header'] ?? '';
      $attachments['drupalSettings']['header'] .= $this->renderer->renderInIsolation($header_render);
    }
    foreach ($view->footer as $footer) {
      $footer_render = $footer->render();
      $attachments['drupalSettings']['footer'] = $attachments['drupalSettings']['footer'] ?? '';
      $attachments['drupalSettings']['footer'] .= $this->renderer->renderInIsolation($footer_render);
    }
    $response->setAttachments($attachments);

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }

}
