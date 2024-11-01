<?php

namespace Drupal\danse\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control at the own user only.
 *
 * @ingroup views_access_plugins
 */
#[ViewsAccess(
  id: 'danse_own_user',
  title: new TranslatableMarkup('Own user'),
  help: new TranslatableMarkup('Will be available to the own user only.'),
)]
class OwnUser extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle(): TranslatableMarkup {
    return $this->t('Own user');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    // No access control.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', 'danse.service::checkAccessInt');
  }

}
