<?php

namespace Drupal\eca_views\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_views\Event\PostBuild;
use Drupal\eca_views\Event\PostExecute;
use Drupal\eca_views\Event\PostRender;
use Drupal\eca_views\Event\PreBuild;
use Drupal\eca_views\Event\PreExecute;
use Drupal\eca_views\Event\PreRender;
use Drupal\eca_views\Event\PreView;
use Drupal\eca_views\Event\QueryAlter;
use Drupal\eca_views\Event\QuerySubstitutions;
use Drupal\eca_views\Event\ViewsBase;
use Drupal\eca_views\Event\ViewsEvents;
use Drupal\views\Entity\View;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Plugin implementation of the ECA Events for content entities.
 *
 * @EcaEvent(
 *   id = "eca_views",
 *   deriver = "Drupal\eca_views\Plugin\ECA\Event\ViewsEventDeriver",
 *   eca_version_introduced = "2.0.0"
 * )
 */
class ViewsEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'query_substitutions' => [
        'label' => 'Views: Query Substitutions',
        'event_name' => ViewsEvents::QUERYSUBSTITUTIONS,
        'event_class' => QuerySubstitutions::class,
      ],
      'pre_view' => [
        'label' => 'Views: Pre View',
        'event_name' => ViewsEvents::PREVIEW,
        'event_class' => PreView::class,
      ],
      'pre_build' => [
        'label' => 'Views: Pre Build',
        'event_name' => ViewsEvents::PREBUILD,
        'event_class' => PreBuild::class,
      ],
      'post_build' => [
        'label' => 'Views: Post Build',
        'event_name' => ViewsEvents::POSTBUILD,
        'event_class' => PostBuild::class,
      ],
      'pre_execute' => [
        'label' => 'Views: Pre Execute',
        'event_name' => ViewsEvents::PREEXECUTE,
        'event_class' => PreExecute::class,
      ],
      'post_execute' => [
        'label' => 'Views: Post Execute',
        'event_name' => ViewsEvents::POSTEXECUTE,
        'event_class' => PostExecute::class,
      ],
      'pre_render' => [
        'label' => 'Views: Pre Render',
        'event_name' => ViewsEvents::PRERENDER,
        'event_class' => PreRender::class,
      ],
      'post_render' => [
        'label' => 'Views: Post Render',
        'event_name' => ViewsEvents::POSTRENDER,
        'event_class' => PostRender::class,
      ],
      'query_alter' => [
        'label' => 'Views: Query Alter',
        'event_name' => ViewsEvents::QUERYALTER,
        'event_class' => QueryAlter::class,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function generateWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    return $ecaEvent->getConfiguration()['view_id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesForWildcard(Event $event, string $event_name, string $wildcard): bool {
    if ($event instanceof ViewsBase) {
      return $wildcard === $event->getView()->id();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'view_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $views = [];
    foreach (View::loadMultiple() as $view) {
      if ($view->status()) {
        $views[$view->id()] = $view->label();
      }
    }
    $form['view_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#default_value' => $this->configuration['view_id'],
      '#description' => $this->t('Select the view from the list.'),
      '#weight' => -50,
      '#options' => $views,
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['view_id'] = $form_state->getValue('view_id');
  }

}
