<?php

/**
 * @file
 * Post update functions for Raven module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Add current_user and request_stack as optional service arguments.
 */
function raven_post_update_new_service_arguments(): void {
}

/**
 * Add new service arguments for the request subscriber.
 */
function raven_post_update_request_subscriber_service_arguments(): void {
}

/**
 * Move database logging from stack middleware to request subscriber.
 */
function raven_post_update_disable_middleware(): void {
}

/**
 * Remove deprecated alter hooks configuration.
 */
function raven_post_update_remove_deprecated_hooks(): TranslatableMarkup {
  Drupal::configFactory()->getEditable('raven.settings')
    ->clear('disable_deprecated_alter')
    ->save();
  return t('The deprecated alter hooks hook_raven_breadcrumb_alter(), hook_raven_filter_alter() and hook_raven_options_alter() have been removed. To alter Sentry functionality, configure an event processor on the Sentry scope, or subscribe to the \Drupal\raven\Event\OptionsAlter event and configure the client options, such as the before_breadcrumb and before_send callbacks.');
}

/**
 * Add new HTTP compression configuration.
 */
function raven_post_update_add_http_compression(): TranslatableMarkup {
  Drupal::configFactory()->getEditable('raven.settings')
    ->set('http_compression', TRUE)
    ->save();
  return t('A configuration toggle has been added for Sentry HTTP compression. HTTP compression was already on by default, so the configuration has been enabled.');
}
