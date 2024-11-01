/**
 * @file
 * Provides mobile toggler for the the Ultimenu blocks.
 */

(function ($, Drupal, drupalSettings, _win, _doc, $HTML) {

  'use strict';

  var NAME = 'ultimenu';
  var ACTIVE = 'active';
  var EXPANDED = 'expanded';
  var IS_NAME = 'is-' + NAME;
  var IS_ULTI = 'is-ulti';
  var IS_ROOT_DESKTOP = IS_ULTI + 'desktop';
  var IS_ROOT_MOBILE = IS_ULTI + 'mobile';
  var IS_ROOT_STICKY = IS_ULTI + 'sticky';
  var IS_ROOT_EXPANDED = IS_NAME + '--' + EXPANDED;
  var IS_ROOT_ACTIVE = IS_NAME + '--' + ACTIVE;
  var IS_ROOT_HOVER = IS_NAME + '--hover';
  var IS_ROOT_HIDING = IS_NAME + '--hiding';
  var IS_ITEM_EXPANDED = 'is-uitem-' + EXPANDED;
  var IS_LINK_ACTIVE = 'is-ulink-' + ACTIVE;
  var IS_HAMBURGER_ACTIVE = 'is-ubtn-' + ACTIVE;
  var IS_AJAX_HIT = 'data-ultiajax-hit';
  var IS_HOVER = false;
  var IS_STICKY = false;
  var C_HIDDEN = 'hidden';
  var C_FLYOUT = 'flyout';
  var IS_FLYOUT = 'is-' + C_FLYOUT;
  var IS_FLYOUT_EXPANDED = IS_FLYOUT + '-' + EXPANDED;
  var C_LINK = NAME + '__link';
  var S_LINK = '.' + C_LINK;
  var S_AJAX_LINK = '.' + NAME + '__ajax';
  var AJAX_TRIGGER = 'data-ultiajax-trigger';
  var C_ULTIMENU_FLYOUT = NAME + '__' + C_FLYOUT;
  var C_OFFCANVAS = IS_NAME + '__canvas-off';
  var C_ONCANVAS = IS_NAME + '__canvas-on';
  var C_BACKDROP = IS_NAME + '__backdrop';
  var C_CARET = NAME + '__caret';
  var S_CARET = '.' + C_CARET;
  var S_BACKDROP = '.' + C_BACKDROP;
  var S_HAMBURGER = '.button--ultiburger';
  var TIMER_HIDING = void 0;
  var TIMER_WAITING = void 0;
  var ADDCLASS = 'addClass';
  var REMOVECLASS = 'removeClass';
  var E_DBLCLICK = 'dblclick';
  var C_TOUCH = 'touchevents';
  var SETTINGS = drupalSettings.ultimenu || {};
  var WIN_SIZE = {};

  // Adds [no-]touchevents HTML classes.
  if ('touchOrNot' in $) {
    $.touchOrNot();
  }

  Drupal.ultimenu = {
    $backdrop: null,
    $hamburger: null,
    $offCanvas: null,
    isInvalid: function (e, selector) {
      return !$.is(e.target, selector) || e.type === E_DBLCLICK;
    },

    viewport: function () {
      WIN_SIZE = $.windowSize();
      var vh = WIN_SIZE.height / 100;

      $HTML.style.setProperty('--vh', vh + 'px');
    },

    breakpoints: function () {
      var me = this;
      var removeClasses = function () {
        $.removeClass($HTML, [
          IS_ROOT_ACTIVE,
          IS_ROOT_HOVER,
          IS_ROOT_EXPANDED,
          IS_ROOT_STICKY
        ]);
        me.closeFlyout();
      };

      var touchClasses = function () {
        $.addClass($HTML, [IS_ROOT_MOBILE, IS_ROOT_ACTIVE]);
        $.removeClass($HTML, IS_ROOT_DESKTOP);
        $.trigger(_win, 'ultimenu:touch');
      };

      var desktopClasses = function () {
        // If hamburger is hidden, meaning hover.
        if (IS_HOVER || me.isHidden($.find(_doc, S_HAMBURGER) || me.$hamburger)) {
          $.addClass($HTML, IS_ROOT_HOVER);

          if (IS_STICKY) {
            $.addClass($HTML, IS_ROOT_STICKY);
            if (me.$offCanvas) {
              setTimeout(function () {
                var vh = me.$offCanvas.offsetHeight;

                $HTML.style.setProperty('--ultiheader', vh + 'px');
              }, 101);
            }
          }
        }
        else {
          $.addClass($HTML, IS_ROOT_ACTIVE);
        }

        $.addClass($HTML, IS_ROOT_DESKTOP);
        $.removeClass($HTML, IS_ROOT_MOBILE);

        $.trigger(_win, 'ultimenu:desktop');
      };

      var toggleClasses = function (e) {
        setTimeout(function () {
          me.viewport();
          removeClasses();

          if ((e && e.matches) || e === C_TOUCH) {
            touchClasses();
          }
          else {
            desktopClasses();
          }
        });
      };

      var isMobile = $.isTouch(toggleClasses);
      toggleClasses(isMobile ? C_TOUCH : null);
    },

    onClickHamburger: function (e) {
      var me = this;

      e.preventDefault();
      e.stopPropagation();

      var $button = e.target;
      var expanded = $.hasClass($HTML, IS_ROOT_EXPANDED);

      if (me.isInvalid(e, S_HAMBURGER)) {
        return false;
      }

      _win.setTimeout(function () {
        $[expanded ? REMOVECLASS : ADDCLASS]($HTML, IS_ROOT_EXPANDED);
        $[expanded ? REMOVECLASS : ADDCLASS]($button, IS_HAMBURGER_ACTIVE);

        me.closeFlyout();
      }, 30);

      // Cannot use transitionend as can be jumpy affected by child transitions.
      if (expanded) {
        _win.clearTimeout(TIMER_HIDING);
        $.addClass($HTML, IS_ROOT_HIDING);

        TIMER_HIDING = _win.setTimeout(function () {
          $.removeClass($HTML, IS_ROOT_HIDING);
        }, 500);
      }

      // Scroll to top in case the current viewport is far below the fold.
      /*
      if (me.$backdrop && !expanded) {
        _win.scroll({
          top: me.$backdrop.offsetTop,
          behavior: 'smooth'
        });
      }
      */
    },

    onResize: function () {
      var me = this;

      me.viewport();
    },

    hamburger: function () {
      var me = this;

      if ($.find(_doc, 'body > ' + S_HAMBURGER) === null) {
        var hamburger = $.find(_doc, S_HAMBURGER);
        if (hamburger) {
          _doc.body.appendChild(hamburger);
        }
      }

      me.$hamburger = $.find(_doc, S_HAMBURGER);

      // Reacts on clicking Ultimenu hamburger button.
      $.on(me.$hamburger, 'click.' + NAME, me.onClickHamburger.bind(me));
    },

    offcanvas: function () {
      var me = this;

      if (!me.$hamburger) {
        return;
      }

      // @todo remove BC.
      var header = $.find(_doc, 'is-ultimenu-canvas-off');
      if (header) {
        $.addClass(header, C_OFFCANVAS);
      }

      // @todo remove BC.
      var siblings = $.findAll(_doc, 'is-ultimenu-canvas-on');
      if (siblings.length) {
        $.addClass(siblings, C_ONCANVAS);
      }

      me.$offCanvas = $.find(_doc, '.' + C_OFFCANVAS);

      if (SETTINGS.canvasOff && SETTINGS.canvasOn) {
        if (!me.$offCanvas) {
          me.$offCanvas = $.find(_doc, SETTINGS.canvasOff);
          $.addClass(me.$offCanvas, C_OFFCANVAS);
        }

        var $onCanvas = $.find(_doc, '.' + C_ONCANVAS);
        if (!$onCanvas) {
          var $onCanvases = $.findAll(_doc, SETTINGS.canvasOn);
          $.addClass($onCanvases, C_ONCANVAS);
        }
      }
    },

    backdrop: function () {
      var me = this;

      if (!me.$offCanvas) {
        return;
      }

      // @todo remove BC.
      var backdrop = $.find(_doc, 'is-ultimenu-canvas-backdrop');
      if (backdrop) {
        $.addClass(backdrop, C_BACKDROP);
      }

      me.$backdrop = $.find(_doc, S_BACKDROP);

      if (!me.$backdrop) {
        var $parent = me.$offCanvas.parentNode;
        var el = _doc.createElement('div');
        el.className = C_BACKDROP;
        $parent.insertBefore(el, $parent.firstElementChild || null);

        me.$backdrop = el;
      }

      $.on(me.$backdrop, 'click.' + NAME, me.onClickBackdrop.bind(me));
    },

    slideToggle: function (el, className, hidden) {
      if (el) {
        $[hidden ? ADDCLASS : REMOVECLASS](el, className);
      }
    },

    executeAjax: function (el) {
      var $li = $.closest(el, 'li');
      var $ajax = $.find($li, S_AJAX_LINK);

      var cleanUp = function () {
        // Removes attribute to prevent this event from firing again.
        el.removeAttribute(AJAX_TRIGGER);
      };

      // The AJAX link will be gone on successful AJAX request.
      if ($ajax) {
        // Hover event can fire many times, prevents from too many clicks.
        if (!$ajax.hasAttribute(IS_AJAX_HIT)) {
          $ajax.click();

          $ajax.setAttribute(IS_AJAX_HIT, 1);
          $.addClass($ajax, C_HIDDEN);
        }

        // This is the last resort while the user is hovering over menu link.
        // If the AJAX link is still there, an error likely stops it, or
        // the AJAX is taking longer time than 1.5 seconds. In such a case,
        // TIMER_WAITING will re-fire the click event, yet on interval now.
        // At any rate, Drupal.Ajax.ajaxing manages the AJAX requests.
        _win.clearTimeout(TIMER_WAITING);
        TIMER_WAITING = _win.setTimeout(function () {
          $ajax = $.find($li, S_AJAX_LINK);
          if ($ajax) {
            $.removeClass($ajax, C_HIDDEN);
            $ajax.click();
          }
          else {
            cleanUp();
          }

          var onces = $.findAll($li, '[data-once]');
          if (onces.length) {
            $.each(onces, function (elOnce) {
              Drupal.attachBehaviors(elOnce);
            });
          }
        }, 1500);
      }
      else {
        cleanUp();
      }
    },

    triggerAjax: function (e) {
      var me = this;
      var target = e.target;

      e.stopPropagation();

      var link = $.hasClass(target, C_CARET) ?
        $.closest(target, S_LINK) : target;

      if ($.hasAttr(link, AJAX_TRIGGER)) {
        me.executeAjax(link);
      }
    },

    onClickBackdrop: function (e) {
      var me = this;

      e.preventDefault();

      if (me.isInvalid(e, S_BACKDROP)) {
        return false;
      }

      if (me.$hamburger) {
        me.$hamburger.click();
      }
    },

    closeFlyout: function (base) {
      base = base || _doc;

      var expands = $.findAll(base, '.' + IS_ITEM_EXPANDED);
      $.removeClass(expands, IS_ITEM_EXPANDED);

      // @todo remove for just above.
      var actives = $.findAll(base, '.' + IS_LINK_ACTIVE);
      var flyouts = $.findAll(base, '.' + IS_FLYOUT_EXPANDED);
      $.removeClass(actives, IS_LINK_ACTIVE);
      $.removeClass(flyouts, IS_FLYOUT_EXPANDED);
    },

    isHidden: function (el) {
      if (el) {
        if (el.offsetParent === null || el.clientHeight === 0) {
          return true;
        }
        var style = _win.getComputedStyle(el);
        return style.display === 'none' || style.visibility === 'hidden';
      }
      return false;
    },

    onClickCaret: function (e) {
      var me = this;
      var $caret = e.target;

      e.preventDefault();
      e.stopPropagation();

      if (me.isInvalid(e, S_CARET)) {
        return false;
      }

      var $link = $.closest($caret, S_LINK);
      var $li = $.closest($link, 'li');
      var $flyout = $link.nextElementSibling;
      var $container = $.closest($li, '.ultimenusub');
      var $submenu = $container ? $.closest($li, '.menu') : null;

      if (!$flyout) {
        return false;
      }

      // If hoverable for desktop, one at a time click should hide flyouts.
      // We let regular mobile toggle not affected, to avoid jumping accordion.
      var hidden = me.isHidden($flyout);
      // Note! Intentionally multi-conditions, not merged, else collapses.
      if ($submenu) {
        if (hidden) {
          me.closeFlyout($submenu);
        }
      }
      else {
        // Only for when desktop hover displaying caret via Use caret option.
        // Flyouts are already being displayed when being hovered, so useless
        // unless Use caret option enabled.
        // To support carets anywhere, do not condition it, so that always
        // one flyout being displayed at a time.
        // if (me.isHidden(me.$hamburger)) {
        me.closeFlyout();
        // }
      }

      // Toggle the current flyout.
      // @todo remove strict checks after another check.
      var isFlyout = $.hasClass($flyout, C_ULTIMENU_FLYOUT);
      var isMenu = $.hasClass($flyout, 'menu') || $.is($flyout, 'ul');
      if (isFlyout || isMenu) {
        $[hidden ? ADDCLASS : REMOVECLASS]($li, IS_ITEM_EXPANDED);
        $[hidden ? ADDCLASS : REMOVECLASS]($link, IS_LINK_ACTIVE);

        me.slideToggle($flyout, IS_FLYOUT_EXPANDED, hidden);
      }
    },

    prepare: function () {
      var me = this;

      IS_HOVER = $.hasClass($HTML, IS_ROOT_HOVER);
      IS_STICKY = $.hasClass($HTML, IS_ROOT_STICKY);

      // Checks viewport height based to set var(--vh).
      $.resize(me.onResize.bind(me))();

      // Moves the hamburger button to the end of the body.
      me.hamburger();

      // Allows hard-coded CSS classes to not use this.
      me.offcanvas();

      // Prepends our backdrop before the main off-canvas element.
      me.backdrop();

      // Reacts on resizing, and breakpoint changes.
      me.breakpoints();
    }

  };

})(dBlazy, Drupal, drupalSettings, this, this.document, this.document.documentElement);
