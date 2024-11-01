<?php

namespace Drupal\raven\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Url;
use Sentry\Dsn;

/**
 * Automatically overrides Security Kit configuration.
 */
class SecKitOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Constructs the Security Kit config overrider.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory, protected ?string $environment = NULL) {
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line Core doesn't yet document the param or return arrays.
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (!in_array('seckit.settings', $names)) {
      return $overrides;
    }
    $config = $this->configFactory->get('raven.settings');
    $dsn = empty($_SERVER['SENTRY_DSN']) ? $config->get('public_dsn') : $_SERVER['SENTRY_DSN'];
    if (NULL === $dsn) {
      return $overrides;
    }
    try {
      $dsn = Dsn::createFromString($dsn);
    }
    catch (\InvalidArgumentException $e) {
      // Raven is incorrectly configured.
      return $overrides;
    }
    if ($config->get('seckit_set_report_uri')) {
      $query['sentry_environment'] = empty($_SERVER['SENTRY_ENVIRONMENT']) ? ($config->get('environment') ?: $this->environment) : $_SERVER['SENTRY_ENVIRONMENT'];
      if ($release = empty($_SERVER['SENTRY_RELEASE']) ? $config->get('release') : $_SERVER['SENTRY_RELEASE']) {
        $query['sentry_release'] = $release;
      }
      $overrides['seckit.settings']['seckit_xss']['csp']['report-uri'] = $overrides['seckit.settings']['seckit_ct']['report_uri'] =
        Url::fromUri($dsn->getCspReportEndpointUrl(), ['query' => $query])->toString();
    }
    if ($config->get('javascript_error_handler')) {
      $seckitConfig = $this->configFactory->getEditable('seckit.settings');
      if ($config->get('show_report_dialog')) {
        $src[] = str_replace(
          ["/{$dsn->getProjectId()}/", '/envelope/'],
          ['/embed/', '/error-page/'],
          $dsn->getEnvelopeApiEndpointUrl()
        );
        if (($url = $config->get('error_embed_url')) && is_string($url)) {
          $src[] = "$url/api/embed/error-page/";
        }
        if ($script_src = $seckitConfig->get('seckit_xss.csp.script-src') ?: $seckitConfig->get('seckit_xss.csp.default-src')) {
          $overrides['seckit.settings']['seckit_xss']['csp']['script-src'] = implode(' ', array_merge([$script_src], $src));
        }
        if ($img_src = $seckitConfig->get('seckit_xss.csp.img-src') ?: $seckitConfig->get('seckit_xss.csp.default-src')) {
          if (!is_string($img_src)) {
            throw new \UnexpectedValueException('Non-string Security Kit CSP rule encountered.');
          }
          $img = explode(' ', $img_src);
          $img[] = 'data:';
          $overrides['seckit.settings']['seckit_xss']['csp']['img-src'] = implode(' ', array_unique($img));
        }
        if ($style_src = $seckitConfig->get('seckit_xss.csp.style-src') ?: $seckitConfig->get('seckit_xss.csp.default-src')) {
          if (!is_string($style_src)) {
            throw new \UnexpectedValueException('Non-string Security Kit CSP rule encountered.');
          }
          $style = explode(' ', $style_src);
          $style[] = "'unsafe-inline'";
          $overrides['seckit.settings']['seckit_xss']['csp']['style-src'] = implode(' ', array_unique($style));
        }
      }
      if ($connect_src = $seckitConfig->get('seckit_xss.csp.connect-src') ?: $seckitConfig->get('seckit_xss.csp.default-src')) {
        $connect = [$connect_src];
        if (!$config->get('tunnel')) {
          $connect[] = $dsn->getEnvelopeApiEndpointUrl();
        }
        if (isset($src)) {
          $connect = array_merge($connect, $src);
        }
        $overrides['seckit.settings']['seckit_xss']['csp']['connect-src'] = implode(' ', $connect);
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'RavenSecKitOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * Creates a configuration object for use during install and synchronization.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase|null
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
