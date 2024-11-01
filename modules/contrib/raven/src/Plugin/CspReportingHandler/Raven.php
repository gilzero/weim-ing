<?php

namespace Drupal\raven\Plugin\CspReportingHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\csp\Csp;
use Drupal\csp\Plugin\ReportingHandlerBase;
use Sentry\Dsn;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CSP Reporting Plugin for a Sentry endpoint.
 *
 * @CspReportingHandler(
 *   id = "raven",
 *   label = "Sentry",
 *   description = @Translation("Reports will be sent to Sentry."),
 * )
 */
class Raven extends ReportingHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line CSP doesn't yet document the configuration array.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ConfigFactoryInterface $configFactory, protected $environment) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line CSP doesn't yet document the configuration array.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      // @phpstan-ignore-next-line CSP doesn't yet document the plugin_definition type.
      $plugin_definition,
      $container->get('config.factory'),
      $container->getParameter('kernel.environment'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterPolicy(Csp $policy): void {
    $config = $this->configFactory->get('raven.settings');
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
    $query['sentry_environment'] = empty($_SERVER['SENTRY_ENVIRONMENT']) ? ($config->get('environment') ?: $this->environment) : $_SERVER['SENTRY_ENVIRONMENT'];
    if ($release = empty($_SERVER['SENTRY_RELEASE']) ? $config->get('release') : $_SERVER['SENTRY_RELEASE']) {
      $query['sentry_release'] = $release;
    }
    $policy->setDirective('report-uri', Url::fromUri($dsn->getCspReportEndpointUrl(), ['query' => $query])->toString());
  }

}
