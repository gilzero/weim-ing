/**
 * @file
 * JavaScript file for the UI Pattern settings module.
 */

(function ($, Drupal, drupalSettings, DrupalCoffee) {

  'use strict';

  /**
   * Attaches ui patterns settings module behaviors.
   *
   * Handles enable/disable token element.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach  ui patterns settings toggle functionality to the page.
   *
   */
  Drupal.behaviors.ups_toggle_token = {
    attach: function () {
      once('ui-patterns-settings-show-token-link', '.js-ui-patterns-settings-show-token-link').forEach(function (elm) {
        $(elm).after($('<a href="#" class="ui-patterns-settings-show-token-a">' + Drupal.t('Browse available token') + '</a>').click(function (event) {
          event.preventDefault();
          $('#ui-patterns-settings-token-link:first a').click();
        }));
      });

      once('ui-patterns-settings-wrapper', '.js-ui-patterns-settings__wrapper').forEach(function (el) {
        var wrapper = $(el);
        var toggler = $('<div class="js-ui-patterns-settings__toggler" title="Use token"></div>');
        $(toggler).click(function () {
          var tokenInput = $('.js-ui-patterns-settings__token', wrapper);
          if ($(wrapper).hasClass('js-ui-patterns-settings--token-has-value')) {
            tokenInput.attr('data-init-val', tokenInput.val());
            tokenInput.val('');
            wrapper.removeClass('js-ui-patterns-settings--token-has-value');
          } else {
            tokenInput.val(tokenInput.attr('data-init-val'));
            wrapper.addClass('js-ui-patterns-settings--token-has-value');
          }
        });
        $('.js-ui-patterns-settings__input-wrapper', wrapper).append(toggler)
        $('.js-ui-patterns-settings__token-wrapper', wrapper).append(toggler.clone(true))
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
