<?php

namespace Drupal\module_builder_devel\Routing;

use Drupal\module_builder_devel\Form\ProcessFormExtra;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the code analysis route to use our form class.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change form class for the process form.
    if ($route = $collection->get('module_builder.analyse')) {
      $route->setDefault('_form', ProcessFormExtra::class);
    }
  }

}
