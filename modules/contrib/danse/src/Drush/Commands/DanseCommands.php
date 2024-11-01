<?php

namespace Drupal\danse\Drush\Commands;

use Drupal\danse\Service;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
class DanseCommands extends DrushCommands {

  /**
   * The DANSE service.
   *
   * @var \Drupal\danse\Service
   */
  protected Service $danse;

  /**
   * Danse commands constructor.
   *
   * @param \Drupal\danse\Service $danse
   *   The DANSE service.
   */
  public function __construct(Service $danse) {
    parent::__construct();
    $this->danse = $danse;
  }

  /**
   * Return an instance of these Drush commands.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\danse\Drush\Commands\DanseCommands
   *   The DANSE commands.
   */
  public static function create(ContainerInterface $container): DanseCommands {
    return new self(
      $container->get('danse.service'),
    );
  }

  /**
   * Create notifications.
   */
  #[CLI\Command(name: 'danse:notifications:create', aliases: ['dnc'])]
  #[CLI\Usage(name: 'danse:notifications:create', description: 'Creates all outstanding notifications.')]
  public function createNotifications(): void {
    $this->danse->createNotifications();
  }

  /**
   * Get status of DANSE event tracking.
   */
  #[CLI\Command(name: 'danse:event-tracking:status', aliases: [])]
  #[CLI\Usage(name: 'danse:event-tracking:status', description: 'Get status of DANSE event tracking.')]
  public function status(): void {
    if ($this->danse->isPaused()) {
      $this->io()->warning('DANSE event tracking is paused.');
    }
    else {
      $this->io()->success('DANSE event tracking is active.');
    }
  }

  /**
   * Pause DANSE event tracking.
   */
  #[CLI\Command(name: 'danse:event-tracking:pause', aliases: [])]
  #[CLI\Usage(name: 'danse:event-tracking:pause', description: 'Pause DANSE event tracking.')]
  public function pause(): void {
    $this->danse->pause();
    $this->io()->success('DANSE event tracking paused.');
  }

  /**
   * Resume DANSE event tracking.
   */
  #[CLI\Command(name: 'danse:event-tracking:resume', aliases: [])]
  #[CLI\Usage(name: 'danse:event-tracking:resume', description: 'Resume DANSE event tracking.')]
  public function resume(): void {
    $this->danse->resume();
    $this->io()->success('DANSE event tracking resumed.');
  }

}
