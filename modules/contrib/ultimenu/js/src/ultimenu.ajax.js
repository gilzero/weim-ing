/**
 * @file
 * Provides AJAX functionality for Ultimenu blocks.
 */

(function ($, Drupal, drupalSettings, _win) {

  'use strict';

  var NAME = 'ultiajax';
  var ID_ONCE = NAME;
  var C_MOUNTED = NAME + '--on';
  var S_ELEMENT = '[data-' + NAME + ']:not(.' + C_MOUNTED + ')';
  var S_AJAX_TRIGGER = '[data-' + NAME + '-trigger]';
  var SETTINGS = drupalSettings.ultimenu || {};

  Drupal.ultimenu = Drupal.ultimenu || {};

  /**
   * Ultimenu utility functions for the ajaxified links, including main menu.
   *
   * @param {HTMLElement} elm
   *   The ultimenu HTML element.
   */
  function doUltimenuAjax(elm) {
    var me = Drupal.ultimenu;
    var smw = SETTINGS.ajaxmw || null;

    if (smw && _win.matchMedia) {
      var mw = _win.matchMedia('(max-device-width: ' + smw + ')');
      if (mw.matches) {
        var links = $.findAll(elm, S_AJAX_TRIGGER);
        if (links.length) {
          // Load all AJAX contents if so configured.
          $.each(links, me.executeAjax.bind(me));

          $.addClass(elm, C_MOUNTED);
          return;
        }
      }
    }

    // Regular mobie/ desktop AJAX.
    $.on(elm, 'mouseover click', S_AJAX_TRIGGER, me.triggerAjax.bind(me));
    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Ultimenu behavior to HTML element [data-ultiajax].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ultimenuAjax = {
    attach: function (context) {
      $.once(doUltimenuAjax, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, this);
