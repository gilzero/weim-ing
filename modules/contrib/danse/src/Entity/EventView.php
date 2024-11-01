<?php

namespace Drupal\danse\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\danse\PluginManager;
use Drupal\danse\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * View builder handler for DANSE events.
 */
final class EventView extends EntityViewBuilder {

  /**
   * The kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected DrupalKernelInterface $kernel;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The DANSE query service.
   *
   * @var \Drupal\danse\Query
   */
  protected Query $query;

  /**
   * The DANSE plugin manager.
   *
   * @var \Drupal\danse\PluginManager
   */
  protected PluginManager $pluginManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): EventView {
    /** @var \Drupal\danse\Entity\EventView $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->kernel = $container->get('kernel');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->query = $container->get('danse.query');
    $instance->pluginManager = $container->get('plugin.manager.danse.plugin');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build) {
    if ($this->routeMatch->getRouteName() !== 'entity.danse_event.canonical') {
      // This call to the build method is not called by the user who wants to
      // view the event, so we should return the default from the parent, as
      // this is most likely called by the event notification system which
      // requires the event to be rendered.
      return parent::build($build);
    }
    $session = $this->request->getSession();
    /**
     * @var \Drupal\danse\Entity\EventInterface $event
     */
    $event = $build['#danse_event'];
    foreach ($this->query->findEventNotificationsForCurrentUser($event) as $notification) {
      try {
        $notification
          ->markSeen()
          ->save();
      }
      catch (EntityStorageException) {
        // @todo Log this exception.
      }
    }

    try {
      $plugin = $this->pluginManager->createInstance($event->getPluginId());
    }
    catch (PluginException) {
      // @todo Log this exception.
      return [];
    }
    $response = new RedirectResponse($plugin->getRedirectUrl($event)->toString());
    $session->save();
    $response->prepare($this->request);
    if ($this->kernel instanceof TerminableInterface) {
      $this->kernel->terminate($this->request, $response);
    }
    $response->send();
    exit;
  }

}
