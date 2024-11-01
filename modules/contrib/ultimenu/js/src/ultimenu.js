/**
 * @file
 * Provides mobile toggler for the the Ultimenu blocks.
 */

(function ($, Drupal, _win) {

  'use strict';

  var NICK = 'ultmn';
  var NAME = 'ultimenu';
  var ID_ONCE = NAME;
  var C_MOUNTED = NAME + '--on';
  var IS_ULTI = 'is-ulti';
  var IS_ULTICARET = IS_ULTI + 'caret';
  var IS_ULTIHOVER = IS_ULTI + 'hover';
  var S_BASE = '[data-' + NAME + ']';
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';
  var C_EXPANDED = 'menu-item--expanded';
  var E_POINTERLEAVE = 'pointerleave.' + NICK;

  Drupal.ultimenu = Drupal.ultimenu || {};

  /**
   * Ultimenu utility functions for the main menu only.
   *
   * @param {HTMLElement} elm
   *   The ultimenu HTML element.
   */
  function process(elm) {
    var me = Drupal.ultimenu;

    var data = {
      caret: $.hasClass(elm, IS_ULTICARET),
      hover: $.hasClass(elm, IS_ULTIHOVER)
    };

    // We'll toggle static expanded class dynamically if so required.
    var items = $.findAll(elm, '.is-uitem-collapsible.' + C_EXPANDED);
    $.removeClass(items, C_EXPANDED);

    // Applies to other Ultimenus.
    $.on(elm, 'click.' + NICK, '.ultimenu__caret', me.onClickCaret.bind(me));

    // Close flyouts if pointer leaving the menu.
    var closeFlyout = function () {
      setTimeout(function () {
        me.closeFlyout();
      }, 1200);
    };

    // Reduce CSS complexity.
    // @todo recheck if any side effects.
    $.on(_win, 'ultimenu:touch', function () {
      if (data.caret) {
        $.removeClass(elm, IS_ULTICARET);
        $.off(elm, E_POINTERLEAVE, closeFlyout);
      }
      if (data.hover) {
        $.removeClass(elm, IS_ULTIHOVER);
      }
    });

    $.on(_win, 'ultimenu:desktop', function () {
      if (data.caret) {
        $.addClass(elm, IS_ULTICARET);
        $.on(elm, E_POINTERLEAVE, closeFlyout);
      }
      if (data.hover) {
        $.addClass(elm, IS_ULTIHOVER);
      }
    });

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Ultimenu behavior to HTML element [data-ultimenu].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ultimenu = {
    attach: function (context) {

      var me = Drupal.ultimenu;
      var ctx = $.context(context, S_BASE);
      var items = $.findAll(ctx, S_ELEMENT);

      if (items.length) {
        me.prepare();

        $.once(process, ID_ONCE, S_ELEMENT, context);
      }
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this);
