<?php

namespace Drupal\push_framework\Drush\Commands;

use Drupal\push_framework\Service;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
class PFCommands extends DrushCommands {

  /**
   * The Push Framework services.
   *
   * @var \Drupal\push_framework\Service
   */
  protected Service $service;

  /**
   * PFCommands constructor.
   *
   * @param \Drupal\push_framework\Service $service
   *   The push framework services.
   */
  final public function __construct(Service $service) {
    parent::__construct();
    $this->service = $service;
  }

  /**
   * Return an instance of these Drush commands.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return static
   *   The instance of Drush commands.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('push_framework.service'),
    );
  }

  /**
   * Execute all items on all source plugins.
   */
  #[CLI\Command(name: 'pf:sources:collect', aliases: [])]
  #[CLI\Usage(name: 'pf:sources:collect', description: 'Execute all items on all source plugins.')]
  public function createNotifications(): void {
    $this->service->collectAllSourceItems();
  }

  /**
   * Push all items in the queue.
   */
  #[CLI\Command(name: 'pf:queue:process', aliases: [])]
  #[CLI\Usage(name: 'pf:queue:process', description: 'Push all items in the queue.')]
  public function processQueue(): void {
    $this->service->processQueue();
  }

}
