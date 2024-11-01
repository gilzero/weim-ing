<?php

namespace Drupal\type_tray\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for type_tray routes.
 */
class TypeTrayRouteSubscriber extends RouteSubscriberBase {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Modify the "/node/add" route to use our own controller instead.
    if ($route = $collection->get('node.add_page')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\type_tray\Controller\TypeTrayController::addPage';
      $route->setDefaults($defaults);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 95];
    return $events;
  }

}
