<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber implementation.
 */
final class DanseRoute extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    if ($route = $collection->get('danse_content.api.subscribe')) {
      $resubscribe_route = $collection->get('pf_notifications.re_subscribe');
      $route->setPath($resubscribe_route->getPath());
      $route->setRequirements($resubscribe_route->getRequirements());
      $route->setDefaults($resubscribe_route->getDefaults());
    }

    if ($route = $collection->get('danse_content.api.unsubscribe')) {
      $resubscribe_route = $collection->get('pf_notifications.re_subscribe');
      $route->setPath($resubscribe_route->getPath());
      $route->setRequirements($resubscribe_route->getRequirements());
      $route->setDefaults($resubscribe_route->getDefaults());
    }
  }

}
