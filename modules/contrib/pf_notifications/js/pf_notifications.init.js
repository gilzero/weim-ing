/**
 * @file
 * Initialize service worker.
 */

((Drupal, drupalSettings) => {
  'use strict';

  /**
   * Old school safety check.
   * @type {object}
   */
  Drupal.AjaxCommands = Drupal.AjaxCommands || {};

  /**
   * A flag, extra safety check.
   * @type boolean
   */
  const isAvailable = 'serviceWorker' in navigator && 'PushManager' in window;

  /**
   * Absolute Url to service worker from pf_notifications.service_worker.js
   * @type string
   */
  const serviceWorkerUrl =
    drupalSettings.pf_notifications && drupalSettings.pf_notifications.serviceWorkerUrl
      ? drupalSettings.pf_notifications && drupalSettings.pf_notifications.serviceWorkerUrl
      : null;
  const resetUrl =
    drupalSettings.pf_notifications && drupalSettings.pf_notifications.resetUrl
      ? drupalSettings.pf_notifications && drupalSettings.pf_notifications.resetUrl
      : null;

  if (isAvailable) {
    window.addEventListener('load', () => {
      init();
    });
  }

  /**
   * The way PWA service worker does - we register ours.
   */
  const init = () => {
    if (serviceWorkerUrl && resetUrl) {
      if (navigator.serviceWorker.controller) {
        const controller = navigator.serviceWorker.controller;
        if (controller.state === 'redundant') {
          unregister({ register: true });
        } else {
          register();
        }
      } else {
        register();
      }
    }
  };

  /**
   * Register service worker.
   */
  const register = (data) => {
    navigator.serviceWorker
      .register(serviceWorkerUrl, { scope: '/' })
      .then((registration) => {
        if (!registration.active) {
          console.debug(
            'Registering Push framework notifications Service Worker. Scope: '.concat(
              registration.scope
            )
          );
        }
      })
      .then(() => {
        if (data && data.redirect) {
          window.location = data.redirect;
        }
      })
      .catch((err) => {
        console.error(
          'Push framework notifications Service Worker registration failed: '.concat(err)
        );
      });
  };

  /**
   * Unregister service worker.
   *
   * @param {object} data
   *   Values such as response.value provided by Drupal ajax command, or any custom set.
   */
  const unregister = (data = {}) => {
    console.debug('Uninstalling service worker.');
    const promise = navigator.serviceWorker
      .getRegistration(serviceWorkerUrl)
      .then((registration) => {
        void registration.unregister().then(() => {
          register(data);
        });
      });
      void Promise.resolve(promise);
  };

  /**
   * ResetCommand to unregister service worker.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   Response object.
   */
  Drupal.AjaxCommands.prototype.pf_notifications_reset = (ajax, response) => {
    unregister({ redirect: response.value.redirect });
  };
})(Drupal, drupalSettings);
