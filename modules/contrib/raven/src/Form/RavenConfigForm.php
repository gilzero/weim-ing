<?php

namespace Drupal\raven\Form;

use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Url;
use Drupal\raven\LogLevel;

/**
 * Implements a Raven Config form.
 */
class RavenConfigForm {

  /**
   * Builds Raven config form.
   *
   * @param mixed[] $form
   *   The logging and errors config form.
   */
  public static function buildForm(array &$form): void {
    if (isset($form['#attached']) && !is_array($form['#attached'])) {
      throw new \InvalidArgumentException('Form #attached key should either not exist, or be an array.');
    }
    $form['#attached']['library'][] = 'raven/admin';
    $form['raven'] = [
      '#type'           => 'details',
      '#title'          => t('Sentry'),
      '#tree'           => TRUE,
      '#open'           => TRUE,
    ];
    $form['raven']['js'] = [
      '#type'           => 'details',
      '#title'          => t('JavaScript'),
      '#open'           => TRUE,
    ];
    $form['raven']['js']['javascript_error_handler'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable JavaScript error handler'),
      '#description'    => t('Check to capture JavaScript errors (if user has the <a target="_blank" href=":url">send JavaScript errors to Sentry</a> permission).', [
        ':url' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-raven'])->toString(),
      ]),
      '#config_target'  => 'raven.settings:javascript_error_handler',
    ];
    $form['raven']['js']['public_dsn'] = [
      '#type'           => 'url',
      '#title'          => t('Sentry DSN'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'public_dsn',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#description'    => t('Sentry client key for current site. This setting can be overridden with the SENTRY_DSN environment variable.'),
    ];
    $form['raven']['js']['browser_traces_sample_rate'] = [
      '#type'           => 'number',
      '#title'          => t('Browser performance tracing sample rate'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'browser_traces_sample_rate',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#description'    => t('If set to 0 or higher, any parent sampled state will be inherited. If set to empty string, performance tracing will be disabled even if parent was sampled.'),
      '#min'            => 0,
      '#max'            => 1,
      '#step'           => 0.000001,
    ];
    $form['raven']['js']['trace_propagation_targets_frontend'] = [
      '#type'           => 'textarea',
      '#title'          => t('Trace propagation targets'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'trace_propagation_targets_frontend',
        [static::class, 'listToString'],
        [static::class, 'stringToList'],
      ),
      '#description'    => t('If the URL of a frontend HTTP request is a same-origin URL or matches one of the additional regular expressions you list here, a baggage HTTP header will be added to the request, allowing traces to be linked across frontend services. Each regular expression will be flagged as case-insensitive, and will match across the entire URL, not just the host. Do not include the pattern delimiter slashes in your regular expressions. For example, entering <code>^https://api\.example\.org/</code> here will configure the regular expression <code>/^https:\/\/api\.example\.org\//i</code>. The baggage header will contain data such as the current route path, environment and release.'),
    ];
    $form['raven']['js']['auto_session_tracking'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable auto session tracking'),
      '#description'    => t('Check to monitor release health by sending a session event to Sentry for each page load; only active if a release is specified below or via the SENTRY_RELEASE environment variable.'),
      '#config_target'  => 'raven.settings:auto_session_tracking',
    ];
    $form['raven']['js']['send_client_reports'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send client reports'),
      '#description'    => t('Send client report (e.g. number of discarded events), if any, when tab is hidden or closed.'),
      '#config_target'  => 'raven.settings:send_client_reports',
    ];
    $form['raven']['js']['send_inp_spans'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send Interaction to Next Paint (INP) spans'),
      '#description'    => t('Check to automatically send an interaction span when an INP event is detected.'),
      '#config_target'  => 'raven.settings:send_inp_spans',
    ];
    $form['raven']['js']['seckit_set_report_uri'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send security header reports to Sentry'),
      '#description'    => t('Check to send CSP and CT reports to Sentry if Security Kit module is installed. This setting is not used if CSP module is installed, which has its own UI to configure sending CSP reports to Sentry.'),
      '#config_target'  => 'raven.settings:seckit_set_report_uri',
      '#disabled'       => !\Drupal::moduleHandler()->moduleExists('seckit'),
    ];
    $form['raven']['js']['show_report_dialog'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Show user feedback dialog'),
      '#description'    => t('Check to allow users to submit a report to Sentry when JavaScript exceptions are thrown.'),
      '#config_target'  => 'raven.settings:show_report_dialog',
    ];
    $form['raven']['js']['error_embed_url'] = [
      '#type'           => 'url',
      '#title'          => t('Error embed URL'),
      '#description'    => t('You will need to define this URL if you want to automatically add CSP rules for the user feedback dialog, and it is not served from the DSN hostname (e.g. enter <code>https://sentry.io</code> if you use the hosted sentry.io).'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'error_embed_url',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#disabled'       => !\Drupal::moduleHandler()->moduleExists('csp') && !\Drupal::moduleHandler()->moduleExists('seckit'),
    ];
    $form['raven']['js']['tunnel'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable error tunneling'),
      '#description'    => t('Tunnel Sentry events through the website. This is useful, for example, to prevent Sentry requests from getting ad-blocked, or for reaching non-public Sentry servers. See more details in <a href=":url" rel="noreferrer">Sentry\'s JavaScript troubleshooting documentation</a>. Note that CSP reports and user feedback reports, if enabled, will not be tunneled. Tunneled requests will use the timeout and HTTP compression settings configured in the PHP section below.', [
        ':url' => 'https://docs.sentry.io/platforms/javascript/troubleshooting/#using-the-tunnel-option',
      ]),
      '#config_target'  => 'raven.settings:tunnel',
    ];
    $form['raven']['js']['test'] = [
      '#type'           => 'button',
      '#value'          => t('Send JavaScript test message to Sentry'),
      '#disabled'       => TRUE,
    ];

    $form['raven']['php'] = [
      '#type'           => 'details',
      '#title'          => t('PHP'),
      '#open'           => TRUE,
    ];
    $form['raven']['php']['client_key'] = [
      '#type'           => 'url',
      '#title'          => t('Sentry DSN'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'client_key',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#description'    => t('Sentry client key for current site. This setting can be overridden with the SENTRY_DSN environment variable.'),
    ];
    $form['raven']['php']['log_levels'] = [
      '#type'           => 'checkboxes',
      '#title'          => t('Log levels'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'log_levels',
        [static::class, 'booleansToCheckboxes'],
        [static::class, 'checkboxesToBooleans'],
      ),
      '#description'    => t('Check the log levels that should be captured by Sentry.'),
      '#options'        => LogLevel::getOptions(),
    ];
    $form['raven']['php']['ignored_channels'] = [
      '#type'           => 'textarea',
      '#title'          => t('Ignored channels'),
      '#description'    => t('A list of log channels for which messages will not be sent to Sentry (one channel per line). Commonly-configured log channels include <em>access denied</em> for 403 errors and <em>page not found</em> for 404 errors.'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'ignored_channels',
        [static::class, 'listToString'],
        [static::class, 'stringToList'],
      ),
    ];
    $form['raven']['php']['ignored_messages'] = [
      '#type'           => 'textarea',
      '#title'          => t('Ignored messages'),
      '#description'    => t('A list of log messages for which messages will not be sent to Sentry (one message per line). Message does not have placeholders replaced, e.g. <em>During rendering of embedded media: the media item with UUID "@uuid" does not exist.</em>'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'ignored_messages',
        [static::class, 'listToString'],
        [static::class, 'stringToList'],
      ),
    ];
    $form['raven']['php']['fatal_error_handler'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable fatal error handler'),
      '#description'    => t('Check to capture fatal PHP errors.'),
      '#config_target'  => 'raven.settings:fatal_error_handler',
    ];
    $form['raven']['php']['drush_error_handler'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable Drush error handler'),
      '#description'    => t('Check to capture errors thrown by Drush commands.'),
      '#config_target'  => 'raven.settings:drush_error_handler',
    ];
    $form['raven']['php']['stack'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable stacktraces'),
      '#config_target'  => 'raven.settings:stack',
      '#description'    => t('Check to add stacktraces to reports.'),
    ];
    $form['raven']['php']['trace'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Reflection tracing in stacktraces'),
      '#config_target'  => 'raven.settings:trace',
      '#description'    => t('Check to enable reflection tracing (function calling arguments) in stacktraces. Warning: This setting allows sensitive data to be logged by Sentry! To enable for exception stacktraces, PHP configuration flag <code>zend.exception_ignore_args</code> must be disabled (see also <code>zend.exception_string_param_max_len</code>).'),
    ];
    $form['raven']['php']['send_user_data'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send user data to Sentry'),
      '#config_target'  => 'raven.settings:send_user_data',
      '#description'    => t('Check to send user email and username to Sentry with each event. Warning: User data can still be sent to Sentry even when this setting is disabled, for example as part of a log message or request body. Custom code is required to scrub personally-identifying information from events before they are sent.'),
    ];
    $form['raven']['php']['send_request_body'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send request body to Sentry'),
      '#config_target'  => 'raven.settings:send_request_body',
      '#description'    => t('Check to send the request body (POST data) to Sentry. Warning: This setting allows sensitive data to be logged by Sentry!'),
    ];
    $form['raven']['php']['modules'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send list of installed Composer packages to Sentry'),
      '#config_target'  => 'raven.settings:modules',
      '#description'    => t('Check to send the list of installed Composer packages to Sentry, including the root project.'),
    ];
    $form['raven']['php']['send_monitoring_sensor_status_changes'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Send Monitoring sensor status changes to Sentry'),
      '#description'    => t('Check to send Monitoring sensor status changes to Sentry if Monitoring module is installed.'),
      '#config_target'  => 'raven.settings:send_monitoring_sensor_status_changes',
      '#disabled'       => !\Drupal::moduleHandler()->moduleExists('monitoring'),
    ];
    $form['raven']['php']['rate_limit'] = [
      '#type'           => 'number',
      '#title'          => t('Rate limit'),
      '#config_target'  => 'raven.settings:rate_limit',
      '#description'    => t('Maximum log events sent to Sentry per-request or per-execution. To disable the limit, set to zero. You may need to set a limit if you have buggy code which generates a large number of log messages.'),
      '#min'            => 0,
      '#step'           => 1,
    ];
    $form['raven']['php']['timeout'] = [
      '#type'           => 'number',
      '#title'          => t('Timeout'),
      '#config_target'  => 'raven.settings:timeout',
      '#description'    => t('Connection timeout in seconds.'),
      '#min'            => 0,
      '#step'           => 0.001,
    ];
    $form['raven']['php']['http_compression'] = [
      '#type'           => 'checkbox',
      '#title'          => t('HTTP compression'),
      '#config_target'  => 'raven.settings:http_compression',
      '#description'    => t('Check to enable HTTP compression, which is recommended unless Sentry or Sentry Relay is running locally. Requires the Zlib PHP extension.'),
      '#disabled'       => !\extension_loaded('zlib'),
    ];
    $form['raven']['php']['performance'] = [
      '#type'           => 'details',
      '#title'          => t('Performance tracing'),
      '#open'           => TRUE,
    ];
    $form['raven']['php']['performance']['request_tracing'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Request/response performance tracing'),
      '#config_target'  => 'raven.settings:request_tracing',
      '#description'    => t('Check to enable performance tracing on the server side for each request/response (if user has the <a target="_blank" href=":url">send performance traces to Sentry</a> permission), excluding pages served from the page cache.', [
        ':url' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-raven'])->toString(),
      ]),
    ];
    $form['raven']['php']['performance']['drush_tracing'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Drush performance tracing'),
      '#config_target'  => 'raven.settings:drush_tracing',
      '#description'    => t('Check to enable performance tracing for each drush command.'),
    ];
    $form['raven']['php']['performance']['database_tracing'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Database performance tracing'),
      '#config_target'  => 'raven.settings:database_tracing',
      '#description'    => t('Check to add database queries to performance tracing.'),
    ];
    $form['raven']['php']['performance']['database_tracing_args'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Database performance tracing query arguments'),
      '#config_target'  => 'raven.settings:database_tracing_args',
      '#description'    => t('Check to add query arguments to database performance tracing. Warning: This setting allows sensitive data to be logged by Sentry!'),
    ];
    $form['raven']['php']['performance']['twig_tracing'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Twig performance tracing'),
      '#config_target'  => 'raven.settings:twig_tracing',
      '#description'    => t('Check to add Twig templates to performance tracing.'),
    ];
    $form['raven']['php']['performance']['404_tracing'] = [
      '#type'           => 'checkbox',
      '#title'          => t('404 response performance tracing'),
      '#config_target'  => 'raven.settings:404_tracing',
      '#description'    => t('Check to enable performance tracing for 404 responses.'),
    ];
    $form['raven']['php']['performance']['traces_sample_rate'] = [
      '#type'           => 'number',
      '#title'          => t('Performance tracing sample rate'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'traces_sample_rate',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#description'    => t('If set to 0 or higher, any parent sampled state will be inherited. If set to empty string, performance tracing will be disabled even if parent was sampled.'),
      '#min'            => 0,
      '#max'            => 1,
      '#step'           => 0.000001,
    ];
    $form['raven']['php']['performance']['trace_propagation_targets_backend'] = [
      '#type'           => 'textarea',
      '#title'          => t('Trace propagation targets'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'trace_propagation_targets_backend',
        [static::class, 'listToString'],
        [static::class, 'stringToList'],
      ),
      '#description'    => t('If the host of a backend HTTP request matches one of the trace propagation targets you list here, a baggage HTTP header will be added to the request, allowing traces to be linked across backend services. Each target should be only a host, not any other parts of the URL. The baggage header will contain data such as the current route path, Drush command, environment and release.'),
    ];
    $form['raven']['php']['cron_monitor_id'] = [
      '#type'           => 'textfield',
      '#title'          => t('Cron monitor slug'),
      '#description'    => t('To enable cron monitoring, add a cron monitor in the Sentry dashboard and specify the monitor slug here.'),
      '#config_target'  => 'raven.settings:cron_monitor_id',
    ];
    $form['raven']['php']['performance']['profiles_sample_rate'] = [
      '#type'           => 'number',
      '#title'          => t('Profiling sample rate'),
      '#config_target'  => new ConfigTarget(
        'raven.settings',
        'profiles_sample_rate',
        NULL,
        [static::class, 'valueToNullOrValue'],
      ),
      '#description'    => t('To enable profiling, configure the profiling sample rate. This feature requires the Excimer PHP extension.'),
      '#min'            => 0,
      '#max'            => 1,
      '#step'           => 0.000001,
      '#disabled'       => !\extension_loaded('excimer'),
    ];
    $form['raven']['php']['test'] = [
      '#type'           => 'button',
      '#value'          => t('Send PHP test message to Sentry'),
      '#disabled'       => TRUE,
    ];
    $form['raven']['environment'] = [
      '#type'           => 'textfield',
      '#title'          => t('Environment'),
      '#config_target'  => 'raven.settings:environment',
      '#description'    => t('The environment in which this site is running (leave blank to use kernel.environment parameter). This setting can be overridden with the SENTRY_ENVIRONMENT environment variable.'),
    ];
    $form['raven']['release'] = [
      '#type'           => 'textfield',
      '#title'          => t('Release'),
      '#config_target'  => 'raven.settings:release',
      '#description'    => t('The release this site is running (could be a version or commit hash). This setting can be overridden with the SENTRY_RELEASE environment variable.'),
    ];
  }

  /**
   * Returns the provided value, or NULL if provided the empty string.
   */
  public static function valueToNullOrValue(string|float|int|null $value): string|float|int|null {
    return $value === '' ? NULL : $value;
  }

  /**
   * Extracts configuration list from string.
   *
   * @return string[]
   *   Configuration as an array of strings.
   */
  public static function stringToList(string $string): array {
    $array = preg_split('/\R/', $string, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($array) ? array_map('trim', $array) : [];
  }

  /**
   * Extracts configuration list from string.
   *
   * @param ?string[] $value
   *   Configuration as an array of strings, or NULL.
   */
  public static function listToString(?array $value): string {
    return implode("\n", $value ?? []);
  }

  /**
   * Extracts log level configuration from checkbox values.
   *
   * @param array<string, string|int> $checkboxes
   *   Checkbox values.
   *
   * @return array<string, bool>
   *   Boolean configuration list.
   */
  public static function checkboxesToBooleans(array $checkboxes): array {
    $booleans = [];
    foreach ($checkboxes as $key => $value) {
      $booleans[$key] = (bool) $value;
    }
    return $booleans;
  }

  /**
   * Returns the log level configuration default value.
   *
   * @param array<string, bool> $booleans
   *   Boolean configuration list.
   *
   * @return array<string, string|int>
   *   Checkboxes default value.
   */
  public static function booleansToCheckboxes(?array $booleans): array {
    $checkboxes = [];
    if (is_array($booleans)) {
      foreach ($booleans as $key => $value) {
        $checkboxes[$key] = $value ? $key : 0;
      }
    }
    return $checkboxes;
  }

}
