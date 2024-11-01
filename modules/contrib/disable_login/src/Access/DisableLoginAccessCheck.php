<?php

namespace Drupal\disable_login\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DisableLoginAccessCheck.
 *
 * Checks access for displaying configuration translation page.
 */
class DisableLoginAccessCheck implements AccessInterface {


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config factory.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an DisableLoginAccessCheck object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->request = $request->getCurrentRequest();
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('module_handler')
     );
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The route which is attempted to be accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    $route_name = $route_match->getRouteName();
    switch ($route_name) {
      // For login pages check for token.
      case 'user.login':
      case 'user.login.http':
        if (!$this->hasValidSecretToken($route_match)) {
          return AccessResult::forbidden();
        }
    }
    return AccessResult::allowed();
  }

  /**
   * Check if the URL has a valid secret token.
   *
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The route which is attempted to be accessed.
   *
   * @return bool
   *   TRUE if the URL has valid secret token.
   */
  private function hasValidSecretToken(RouteMatch $route_match) {
    // Uncomment the following line to disable this module if you
    // are locked out because you forgot the key/value pair and
    // are not able to login.
    // return TRUE;
    // Check key value pair for user/login routes.
    $route_name = $route_match->getRouteName();
    switch ($route_name) {
      // For login pages check for token.
      case 'user.login':
      case 'user.login.http':
        $config = $this->configFactory->get('disable_login.settings');
        // If login pages are protected based on the configuration for
        // the environment, check for key.
        if ($config->get('disable_login')) {
          $key_name = $config->get('querystring');
          $secret_key = $config->get('secret');
          // Allow other modules to alter the key with custom logic
          // for example cycle through keys or based on month etc.
          $this->moduleHandler->alter('disable_login_key', $secret_key);
          $key_value = $this->request->get($key_name);
          if ($key_value == $secret_key) {
            return TRUE;
          }
          else {
            return FALSE;
          }
        }
      default:
        // Do nothing.
    }
    // Protect only those pages that require the key.
    // Return TRUE by default.
    return TRUE;
  }

}
