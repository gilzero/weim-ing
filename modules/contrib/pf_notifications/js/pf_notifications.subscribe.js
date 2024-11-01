/**
 * @file
 * Manage subscription and unsubscription for Push notifications.
 */

((Drupal) => {
  'use strict';

  /**
   * Subscription operation event. Dispatched after post for a rest resource.
   */
  document.addEventListener('pushSubscription', (event) => {
    const data = event.detail;
    // Super-double-check, in case of any namespace conflict.
    if (!data) {
      return;
    }
    if (!data.skip) {
      // Send direct notification about subscription.
      notify(data);
    } else {
      // Redirect to default Drupal redirection
      // upon submitting an entity's form.
      if (data.redirect) {
        window.location = data.redirect;
      }
    }
  });

  /**
   * Wrap all in Drupal behaviors.
   *
   * @type {{attach: Drupal.behaviors.pf_notifications_subscription.attach}}
   */
  Drupal.behaviors.pf_notifications_subscription = {
    attach: (context, settings) => {
      /**
       * Old school safety check.
       * @type {object}
       */
      Drupal.AjaxCommands = Drupal.AjaxCommands || {};

      /**
       * SubscriptionCommand to register our subscription.
       *
       * @param {Drupal.Ajax} [ajax]
       *   An Ajax object.
       * @param {object} response
       *   Object with data, provided by ajax response.
       * @param {number} [status]
       *   The HTTP status code.
       */
      Drupal.AjaxCommands.prototype.pf_notifications_subscription = (ajax, response, status) => {
        if (!response || status !== 'success') {
          console.error(
            Drupal.t('Something went wrong, check your php/drupal logs for possible further info.')
          );
          return;
        }
        if (!response.value || typeof response.value !== 'object') {
          return;
        }

        const data = response.value;
        if (!data.subscribe) {
          if (data.redirect) {
            window.location = data.redirect;
          }
          return;
        }
        const subscriptions = typeof data.subscribe === 'object' ? data.subscribe : null;
        if (subscriptions && subscriptions.skip) {
          if (!data.skip) {
            notify(data);
          }
          return;
        }
        // Payload for subscription (welcome like) notification.
        data.payload = {
          body: data.tokens.body_raw,
          url: data.tokens.url,
          icon: data.tokens.icon
        };

        // Subscription promise, subscribe or unregister push notifications
        // in the given browser/device.
        subscription(data, subscriptions);
      };
    }
  };

  /**
   * Process tokens for data for notification and widget.
   *
   * @param {Object} data
   *   Data array from ajax response.
   *   @see Service/PushNotifications::subscriptionResponse
   * @param {Object} subscriptions
   *   An existing subscriptions,
   *   as found in danse's rows of users_data table.
   */
  const subscription = (data, subscriptions) => {
    if (!checkIsAvailable()) {
      return false;
    }
    navigator.serviceWorker.getRegistration(data.serviceWorkerUrl).then((registration) => {
      if (!registration) {
        return;
      }
      registration.pushManager
        .getSubscription()
        .then((subscription) => {
          if (subscription) {
            return subscription;
          } else {
            var public_key = data.public_key;
            var vapidKey = urlBase64ToUint8Array(public_key);
            return registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: vapidKey
            });
          }
        })
        .then((subscription) => {
          let operation = 'subscribed';
          if (subscriptions) {
            Object.values(subscriptions).forEach((value) => {
              if (value.endpoint === subscription.endpoint) {
                operation = 'unsubscribed';
              }
            });
          }

          // Update a list of subscribed clients (browsers/devices).
          updateClients(subscription, data, operation, subscriptions);

          return {
            subscription: subscription,
            data: data,
            operation: operation
          };
        })

        // Send this now to our rest resource.
        .then(send)
        // Or log (any) error.
        .catch((error) => {
          console.error(error);
        });
    });
  };

  /**
   * Make a POST request to modify subscription data in users_data table.
   *
   * @param {Object} response
   *   A current Push subscription object.
   */
  const send = (response) => {
    const subscription = response.subscription;
    const data = response.data;
    const operation = response.operation;

    const key = subscription.getKey('p256dh');
    const token = subscription.getKey('auth');
    const endpoint = subscription.endpoint;

    // Get CSRF Token and then subscribe.
    fetch(Drupal.url('session/token'))
      .then((response) => response.text())
      .then((csrfToken) => {
        const jsonData = Object.assign(
          {
            // redirect: data.redirect,
            danse_key: data.danse_key,
            danse_active: data.danse_active,
            key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
            token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
            endpoint: endpoint,
            subscribe: operation
          },
          data.entity_data
        );

        // Subscribe the user.
        fetch(data.subscribeUrl, {
          method: 'POST',
          body: JSON.stringify(jsonData),
          headers: {
            'Content-type': 'application/json; charset=UTF-8',
            'X-CSRF-Token': csrfToken
          }
        })
          .then(() => {
            // Now that we have subscription data - update tokens.
            data.tokens = tokens(subscription, data, operation);
            // Handle DANSE widget according to the actual subscription.
            widget(data, operation);
            // Finally dispatch this event for successive operations.
            const event = new CustomEvent('pushSubscription', { detail: data });
            document.dispatchEvent(event);
            // Log operation in browser console.
            const label = data.tokens.label ? ' "' + data.tokens.label + '" ' : '';
            const client = data.tokens.client ? ' with ' + data.tokens.client : '';
            console.debug(data.tokens[operation] + label + client);
          })
          .catch((error) => {
            console.error(error);
          });
      });
  };

  /**
   * Send direct notification about subscription.
   *
   * @param {Object} data
   *   Data array from ajax response.
   *   @see Service/PushNotifications::subscriptionResponse
   */
  const notify = (data) => {
    doNotification(data.title, data.payload).then((promise) => {
      promise().then(() => {
        // Redirect to default Drupal redirection
        // upon submitting an entity's form.
        if (data.redirect) {
          window.location = data.redirect;
        }
      });
    });
  };

  /**
   * Update a list of subscribed clients (browsers/devices).
   *
   * @param {PushSubscription} subscription
   *   A current Push subscription object.
   * @param {Object} data
   *   Data array from ajax response.
   *   @see Service/PushNotifications::subscriptionResponse
   * @param {string} operation
   *   A current operation, "subscribed" or "unsubscribed".
   * @param {Object} subscriptions
   *   An existing subscriptions,
   *   as found in danse's rows of users_data table.
   *
   * @return {Object}
   *   Updated tokens, including a current client and a list of clients.
   */
  const updateClients = (subscription, data, operation, subscriptions) => {
    Object.keys(data.tokens.push_services).forEach((push_service) => {
      const match = push_service.replace('*', '');
      if (subscription.endpoint.indexOf(match) > -1) {
        if (operation === 'unsubscribed' && subscriptions) {
          Object.values(subscriptions).forEach((value) => {
            if (
              value.endpoint === subscription.endpoint &&
              subscription.endpoint.indexOf(match) > -1
            ) {
              data.tokens.client = data.tokens.push_services[push_service];
              if (data.tokens.clients.length) {
                data.tokens.clients = data.tokens.clients.filter((c) => {
                  return c !== data.tokens.client;
                });
              } else {
                data.tokens.clients = [];
              }
            }
          });
        } else {
          data.tokens.client = data.tokens.push_services[push_service];
          if (data.tokens.clients.indexOf(data.tokens.client) < 0) {
            data.tokens.clients.push(data.tokens.client);
          }
        }
      }
    });
    return data.tokens;
  };

  /**
   * Process tokens for data for notification and widget.
   *
   * @param {PushSubscription} subscription
   *   A current Push subscription object.
   * @param {Object} data
   *   Data array from ajax response.
   *   @see Service/PushNotifications::subscriptionResponse
   * @param {string} operation
   *   A current operation, "subscribed" or "unsubscribed".
   *
   * @return {Array}
   *   Processed notification wnd widget related data.
   */
  const tokens = (subscription, data, operation) => {
    if (data.tokens.op_push) {
      const push = operation === 'subscribed' ? 'push' : 'stop_pushing';
      data.title = data.tokens.title_raw.replace('@op_push', data.tokens[push]);
      data.payload.body = data.payload.body.replace('@op_push', data.tokens[push]);
    }
    if (data.tokens.op) {
      data.payload.body = data.payload.body.replace('@op', data.tokens[operation]);
      data.title = data.title.replace('@op', data.tokens[operation]);
    }
    if (data.tokens.clients.length) {
      if (data.tokens.op_clients) {
        data.title = data.title.replace('@clients', data.tokens.clients.join(', '));
        data.payload.body = data.payload.body.replace('@clients', data.tokens.clients.join(', '));
      }
    }
    if (data.tokens.op_client && data.tokens.client) {
      data.title = data.title.replace('@client', data.tokens.client);
      data.payload.body = data.payload.body.replace('@client', data.tokens.client);
    }
    return data.tokens;
  };

  /**
   * Generate new link in the danse widget.
   *
   * @param {Object} data
   *   Data array from ajax response.
   *   @see Service/PushNotifications::subscriptionResponse
   * @param {string} operation
   *   A current operation, "subscribed" or "unsubscribed".
   */
  const widget = (data, operation) => {
    if (data.widget_selector) {
      const widget = document.getElementById(data.widget_selector);
      if (widget) {
        let index = data.widget_link_index;
        const widgetItems = widget.querySelector('ul.dropbutton');
        if (widgetItems && widgetItems.childNodes.length) {
          if (widgetItems.querySelector('.dropbutton-toggle')) {
            index += 1;
          }

          const widgetItem = widgetItems.children[index];
          if (widgetItem) {
            const widgetLink = widgetItem.querySelector('a.danse-subscription-operation');
            if (widgetLink) {
              if (operation === 'subscribed') {
                if (data.tokens.clients.length) {
                  data.tokens.clients.forEach(() => {
                    const bracket = data.tokens.danse_widget_label.indexOf(')') + 1;
                    let markup = data.tokens.subscribed;
                    markup += ' (' + data.tokens.clients.join(', ') + ')';
                    if (bracket > 1) {
                      markup += data.tokens.danse_widget_label.substring(
                        bracket,
                        data.tokens.danse_widget_label.length
                      );
                    } else {
                      if (data.danse_active === 1) {
                        markup = data.tokens.danse_widget_label.replace(
                          data.tokens.re_subscribe,
                          markup
                        );
                      } else {
                        markup = data.tokens.danse_widget_label.replace(
                          data.tokens.subscribe,
                          markup
                        );
                      }
                    }
                    widgetLink.innerHTML = markup;
                  });
                }
              } else if (operation === 'unsubscribed') {
                const bracket = data.tokens.danse_widget_label.indexOf(')') + 1;
                let markup = '';
                if (bracket > 1) {
                  if (data.tokens.clients.length) {
                    markup += data.tokens.subscribed;
                    markup += ' (' + data.tokens.clients.join(', ') + ')';
                    markup += data.tokens.danse_widget_label.substring(
                      bracket,
                      data.tokens.danse_widget_label.length
                    );
                  } else {
                    markup += data.tokens.subscribe;
                    markup += data.tokens.danse_widget_label.substring(
                      bracket,
                      data.tokens.danse_widget_label.length
                    );
                  }
                  widgetLink.innerHTML = markup;
                } else {
                  widgetLink.innerHTML = data.tokens.danse_widget_label;
                }
              }
            }
          }
        }
      }
    }
  };

  /**
   * Notification generate async call.
   *
   * @param {string} title
   *   The notification title.
   * @param {Object} payload
   *   Payload object for Notification API.
   * @param {boolean} notification
   *   Whether to return Notification instance.
   *
   * @return {Notification|Promise}
   *    Generated notification or a promise for further chaining.
   */
  const doNotification = async function (title, payload, notification = false) {
    const registration = await navigator.serviceWorker.getRegistration();
    let notificationInstance;

    const sendNotification = async () => {
      if (Notification.permission === 'granted') {
        showNotification(title, payload);
      } else {
        if (Notification.permission !== 'denied') {
          const permission = await Notification.requestPermission();
          if (permission === 'granted') {
            showNotification(title, payload);
          }
        }
      }
    };

    const showNotification = (title, payload) => {
      if (registration && 'showNotification' in registration) {
        notificationInstance = registration.showNotification(title, payload);
      } else {
        notificationInstance = new Notification(title, payload);
      }
    };
    return notification ? notificationInstance : sendNotification;
  };

  /**
   * Convert string to Unit8Array
   *
   * @param {string} base64String
   *   String to be converted.
   *
   * @return ArrayBuffer
   *   String as an array buffer.
   */
  const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  };

  /**
   * Check if we can use Push Notifications in this browser/device.
   *
   * @returns bool
   */
  const checkIsAvailable = () => {
    return 'serviceWorker' in navigator && 'PushManager' in window;
  };
})(Drupal);
