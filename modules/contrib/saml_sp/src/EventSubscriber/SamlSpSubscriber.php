<?php

namespace Drupal\saml_sp\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use OneLogin\Saml2\Utils;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Subscribes to relevant events.
 */
class SamlSpSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new SamlSpSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, AccountProxyInterface $current_user, DateFormatterInterface $date_formatter) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Checks to be sure the certificate has not expired.
   */
  public function checkForCertExpiration(RequestEvent $event) {
    $config = $this->configFactory->get('saml_sp.settings');
    $user = $this->currentUser;
    if ($user->hasPermission('configure saml sp') &&
      function_exists('openssl_x509_parse') &&
      !empty($config->get('cert_location')) &&
      file_exists($config->get('cert_location'))
    ) {
      $encoded_cert = trim(file_get_contents($config->get('cert_location')));
      $cert = openssl_x509_parse(Utils::formatCert($encoded_cert));
      $test_time = new DrupalDateTime();
      if ($cert['validTo_time_t'] < $test_time->getTimestamp()) {
        $markup = new TranslatableMarkup('Your site\'s SAML certificate is expired. Please replace it with another certificate and request an update to your Relying Party Trust (RPT). You can enter in a location for the new certificate/key pair on the <a href="@url">SAML Service Providers</a> page. Until the certificate/key pair is replaced your SAML authentication service will not function.', [
          '@url' => Url::fromRoute('saml_sp.admin')->toString(),
        ]);
        $this->messenger->addMessage($markup, MessengerInterface::TYPE_ERROR, FALSE);
      }
      elseif (($cert['validTo_time_t'] - $test_time->getTimestamp()) < (60 * 60 * 24 * 30)) {
        $markup = new TranslatableMarkup('Your site\'s SAML certificate will expire in %interval. Please replace it with another certificate and request an update to your Relying Party Trust (RPT). You can enter in a location for the new certificate/key pair on the <a href="@url">SAML Service Providers</a> page. Failure to update this certificate and update the Relying Party Trust (RPT) will result in the SAML authentication service not working.', [
          '%interval' => $this->dateFormatter->formatInterval($cert['validTo_time_t'] - $test_time->getTimestamp(), 2),
          '@url' => Url::fromRoute('saml_sp.admin')->toString(),
        ]);
        $this->messenger->addMessage($markup, MessengerInterface::TYPE_WARNING, FALSE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['checkForCertExpiration'];
    return $events;
  }

}
