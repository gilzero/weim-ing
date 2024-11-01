<?php

namespace Drupal\moderation_dashboard\Plugin\views\access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control for Scheduler.
 *
 * @ingroup views_access_plugins
 */
#[ViewsAccess(
  id: 'moderation_dashboard',
  title: new TranslatableMarkup('Moderation Dashboard Access'),
  help: new TranslatableMarkup('Custom access to the Moderation Dashboard.'),
)]
class ModerationDashboard extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected AccessCheckInterface $moderation_dashboard_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('moderation_dashboard.access_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->moderation_dashboard_access->access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_access_moderation_dashboard', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
