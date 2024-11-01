<?php

namespace Drupal\saml_sp_drupal_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\saml_sp\Entity\Idp;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SAML Drupal Login event subscriber.
 */
class SamlSpDrupalLoginSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messaging system.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private MessengerInterface $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private AccountInterface $currentUser;

  /**
   * Constructs a SamlSpDrupalLoginSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_manager
   *   The config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messaging system.
   */
  public function __construct(ConfigFactoryInterface $config_manager, ModuleHandlerInterface $module_handler, AccountInterface $currentUser, MessengerInterface $messenger) {
    $this->configFactory = $config_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    // If the user is an Admin:
    $is_admin = $this->currentUser->hasPermission('administer site configuration');
    $seckit_exists = $this->moduleHandler->moduleExists('seckit');
    $config = $this->configFactory->get('saml_sp_drupal_login.config');
    if ($is_admin && $seckit_exists) {
      // The security kit module is enabled, so get its config.
      $seckit_config = $this->configFactory->get('seckit.settings');
      if ($seckit_config->get('seckit_csrf.origin')) {
        // CSRF origin checking is enabled, so check each enabled IdP against
        // the list to make sure it is allowed.
        $idp_configs = $config->get('idp');
        $idps = Idp::loadMultiple();
        $whitelist = explode(',', $seckit_config->get('seckit_csrf.origin_whitelist'));
        foreach ($whitelist as &$item) {
          // Strip out all whitespace from the item to make matching easier.
          $item = trim($item);
        }
        foreach ($idp_configs as $key => $value) {
          if ($value) {
            // This IdP is enabled.
            $this_idp = $idps[$key];
            $login_url = parse_url($this_idp->getLoginUrl());
            $login_url = $login_url['scheme'] . '://' . $login_url['host'];
            $logout_url = parse_url($this_idp->getLoginUrl());
            $logout_url = $logout_url['scheme'] . '://' . $logout_url['host'];
            if (array_search($login_url, $whitelist) === FALSE) {
              $this->messenger->addError($this->t('The enabled Identity Provider (IdP) %idp_label is configured with the login url of %url. The Security Kit module is enabled and configured to check the origin of requests to prevent for Cross Site Request Forgery (CSRF). This setting is not configured to allow the url "%csrf_url" which will prevent users from successflly using this IdP to login, users will receive a "403 Access Denied" page when they attempt to login. You MUST add "%csrf_url" to the "Cross-Site Request Forgery" "Allow Requests From" setting on the <a href="@seckit_settings_url">Security Kit settings page</a> before users can successfully log in.', [
                '%url' => $this_idp->getLoginUrl(),
                '%csrf_url' => $login_url,
                '%idp_label' => $this_idp->label(),
                '@seckit_settings_url' => Url::fromRoute('seckit.settings')->toString(),
              ]));
            }

            // We always check login url; if logout is enabled we also check
            // the logout url.
            if ($config->get('logout') && (array_search($logout_url, $whitelist) === FALSE)) {
              $this->messenger->addError($this->t('The enabled Identity Provider (IdP) %idp_label is configured with the logout url of %url. The Security Kit module is enabled and configured to check the origin of requests to prevent for Cross Site Request Forgery (CSRF). This setting is not configured to allow the url "%csrf_url" which may prevent users from successflly using this IdP to logout, users may receive a "403 Access Denied" page when they attempt to logout. You MUST add "%csrf_url" to the "Cross-Site Request Forgery" "Allow Requests From" setting on the <a href="@seckit_settings_url">Security Kit settings page</a>.', [
                '%url' => $this_idp->getLogoutUrl(),
                '%csrf_url' => $logout_url,
                '%idp_label' => $this_idp->label(),
                '@seckit_settings_url' => Url::fromRoute('seckit.settings')->toString(),
              ]));
            }
          }
        }
      }
    }
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    // @todo Place code here.
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
      // @codingStandardsIgnoreLine
      // KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
