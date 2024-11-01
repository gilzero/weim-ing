<?php

namespace Drupal\raven\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Sentry\Dsn;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for CSP events.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists(CspEvents::class)) {
      return [];
    }

    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    return $events;
  }

  /**
   * CspSubscriber constructor.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * Alter CSP policy to allow Sentry to send JS errors.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $policy = $alterEvent->getPolicy();
    $config = $this->configFactory->get('raven.settings');
    if (!$config->get('javascript_error_handler')) {
      return;
    }
    $dsn = empty($_SERVER['SENTRY_DSN']) ? $config->get('public_dsn') : $_SERVER['SENTRY_DSN'];
    if (NULL === $dsn) {
      return;
    }
    try {
      $dsn = Dsn::createFromString($dsn);
    }
    catch (\InvalidArgumentException $e) {
      // Raven is incorrectly configured.
      return;
    }
    $connect = [];
    if (!$config->get('tunnel')) {
      $connect[] = $dsn->getEnvelopeApiEndpointUrl();
    }
    if ($config->get('show_report_dialog')) {
      $initial_url = str_replace(
        ["/{$dsn->getProjectId()}/", '/envelope/'],
        ['/embed/', '/error-page/'],
        $dsn->getEnvelopeApiEndpointUrl()
      );
      $script[] = $initial_url;
      if (($final_url = $config->get('error_embed_url')) && is_string($final_url)) {
        $connect[] = $script[] = "$final_url/api/embed/error-page/";
      }
      else {
        $connect[] = $initial_url;
      }
      $policy->fallbackAwareAppendIfEnabled('script-src', $script);
      $policy->fallbackAwareAppendIfEnabled('script-src-elem', $script);
      $policy->fallbackAwareAppendIfEnabled('img-src', 'data:');
      $policy->fallbackAwareAppendIfEnabled('style-src', Csp::POLICY_UNSAFE_INLINE);
      $policy->fallbackAwareAppendIfEnabled('style-src-elem', Csp::POLICY_UNSAFE_INLINE);
    }
    $policy->fallbackAwareAppendIfEnabled('connect-src', $connect);
  }

}
