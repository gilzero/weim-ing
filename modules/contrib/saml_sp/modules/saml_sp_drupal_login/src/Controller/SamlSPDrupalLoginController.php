<?php

namespace Drupal\saml_sp_drupal_login\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\saml_sp\Entity\Idp;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the SAML SP module.
 */
class SamlSPDrupalLoginController extends ControllerBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Initiate a SAML login for the given IdP.
   */
  public function initiate(Idp $idp) {
    $config = $this->config('saml_sp_drupal_login.config');

    $this->checkSecKit($idp);

    if ($this->currentUser()->isAuthenticated()) {
      $redirect_path = $config->get('logged_in_redirect');
      if ($this->config('saml_sp.settings')->get('debug')) {
        _saml_sp__debug('$redirect_path', $redirect_path);
      }
      if (strpos($redirect_path, '/') === 0) {
        $url = URL::fromUserInput($redirect_path);
      }
      else {
        $url = URL::fromRoute($redirect_path);
      }
      // The user is already logged in, so redirect.
      return new RedirectResponse($url->toString());
    }

    // Start the authentication process; invoke
    // saml_sp_drupal_login__saml_authenticate() when done.
    $callback = 'saml_sp_drupal_login__saml_authenticate';
    $forceAuthn = $config->get('force_authentication') ?? FALSE;
    $return = saml_sp_start($idp, $callback, $forceAuthn);
    if (!empty($return)) {
      // Something was returned, echo it to the screen.
      return $return;
    }
  }


  /**
   * check if Security Kit module is installed with CSRF protection enabled,
   * if so make sure this url is in the whitelist
   *
   */
  protected function checkSecKit(Idp $idp) {

    if (!$this->moduleHandler()->moduleExists('seckit')) {
      // seckit module not installed
       return;
    }

    //get the url for the idp
    $login_url = parse_url($idp->getLoginUrl());
    $seckit_config = $this->configFactory->get('seckit.settings');
    $csrf_url = $login_url['scheme'] . '://' . $login_url['host'];
    if (!str_contains($seckit_config->get('seckit_csrf.origin_whitelist'), $csrf_url)) {
      // the csrf whitelist doesn't contain the login host
      // make sure IdP is allowed in CSRF settings
      $seckit_config = $this->configFactory->getEditable('seckit.settings');
      $whitelist = $seckit_config->get('seckit_csrf.origin_whitelist');
      $whitelist = explode(',', $whitelist);
      if (!empty($whitelist)) {
        foreach ($whitelist as $key => &$item) {
          $item = trim($item);
          if (empty($item)) {
            unset($whitelist[$key]);
          }
        }
      }
      $whitelist[] = $csrf_url;
      $whitelist = implode(', ', $whitelist);

      $seckit_config->set('seckit_csrf.origin_whitelist', $whitelist)->save();
      $this->getLogger('saml_sp')->info('User attempting to log in without Security Kit (SecKit) Cross-Site Request Forgery (CSRF) setting properly configured. The login url %idp_login_url for %idp_label (%idp_id) was not in the CSRF Whitelist. the folloing url %csrf_url was added to the whitelist. If the login url was not in the whitelist and CSRF protection was turned on the login request would have failed.', [
        '%csrf_url' => $csrf_url,
        '%idp_id' => $idp->id(),
        '%idp_label' => $idp->label(),
        '%idp_login_url' => $idp->getLoginUrl()
      ]);
    }

  }

  /**
   * Tests condition for requesting accounts.
   */
  public function access(AccountInterface $account) {
    $authenticated = saml_sp_drupal_login_is_authenticated();
    return AccessResult::allowedIf($account->isAnonymous() && $authenticated);
  }

}
