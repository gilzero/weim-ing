(function ($, Drupal, once) {

  'use strict'

  Drupal.behaviors.simplePopupBlocks = {
    attach: function (context, settings) {
      // Global variables
      var popup_settings = drupalSettings.simple_popup_blocks.settings,
        _html = document.documentElement, windowWidth = $(window).width();

      $.each(popup_settings, function (index, values) {

        // No popup when the window width is less than the trigger width.
      if (windowWidth < values.trigger_width) {return null;};

        // Declaring variable inside foreach - so it will not global.
        var modal_class = '',
          block_id = values.identifier,
          visit_counts_arr = values.visit_counts.split(','),
          allow_cookie = true,
          read_cookie = '',
          cookie_val = 1,
          cookie_days = values.cookie_expiry || 100,
          match = 0,
          css_identity = '',
          spb_popup_id = '',
          modal_close_class = '',
          modal_minimize_class = '',
          modal_minimized_class = '',
          layout_class = '',
          class_exists = false,
          delays = '',
          browser_close_trigger = true,
          use_time_frequency = values.use_time_frequency,
          time_frequency = values.time_frequency,
          time_frequency_cookie = 0,
          next_popup = 0,
          current_timestamp = 0,
          show_minimized_button = values.show_minimized_button,
          show_model = true
        // Always show popup, so prevent from creating cookie
        if (visit_counts_arr.length == 1 && visit_counts_arr[0] == 0 && use_time_frequency == 0) {
          allow_cookie = false
        }
        // Check to see if the block exists in the current page.
        var element = document.getElementById(block_id);
        if (typeof(element) != 'undefined' && element != null) {
        // Creating cookie
        if (allow_cookie == true) {
        if (use_time_frequency == 0) {
          read_cookie = readCookie('spb_' + block_id)
          if (read_cookie) {
            cookie_val = + read_cookie + 1
            createCookie('spb_' + block_id, cookie_val, cookie_days)
          }
          else {
            createCookie('spb_' + block_id, cookie_val, cookie_days)
          }
          // Match cookie
          cookie_val = cookie_val.toString()
          match = $.inArray(cookie_val, visit_counts_arr)

          if (match === -1) {
            show_model = false
          }
          }
          else {
          time_frequency_cookie = readCookie('spb_time' + block_id)
          current_timestamp = Math.floor(Date.now() / 1000)
          next_popup = current_timestamp + parseInt(time_frequency, 10)

          if (time_frequency_cookie) {
            // If current_timestamp is greater than cookie time show the popup.
            if (current_timestamp >= time_frequency_cookie) {
              match = 1
            }
            // This should allow the time frequency to be adjusted down after
            // the cookie has been set.
            else if (next_popup <= time_frequency_cookie) {
              match = 1
            }
            else {
              match = -1
              show_model = false
            }

            // Create new cookie for popup.
            if (match === 1) {
              createCookie('spb_time' + block_id, next_popup, cookie_days)
            }
          }
          else {
            createCookie('spb_time' + block_id, next_popup, cookie_days)
          }
          }
        }

        }
        // Set css selector
        css_identity = '.'
        if (values.css_selector == 1) {
          css_identity = '#'
        }

        // Assign dynamic css classes
        spb_popup_id = 'spb-' + block_id
        modal_class = block_id + '-modal'
        modal_close_class = block_id + '-modal-close'
        modal_minimize_class = block_id + '-modal-minimize'
        modal_minimized_class = block_id + '-modal-minimized'
        layout_class = '.' + modal_class + ' .spb-popup-main-wrapper'
        // Wrap arround elements
        once('wrap-arround', css_identity + block_id).forEach(function(elem) {
          $(elem).wrap($('<div class="' + modal_class + '"></div>'))
        });
        // Hide the popup initially
        once('hide-popup', '.' + modal_class).forEach(function(elem) {
          $(elem).hide()
        })

        // Wrap remaining elements
        if ($(css_identity + block_id).closest('.spb-popup-main-wrapper').length) {
          return;
        }
        $(css_identity + block_id).
          wrap($('<div class="spb-popup-main-wrapper"></div>'))
        $('.' + modal_class).
          wrap('<div id="' + spb_popup_id +
            '" class="simple-popup-blocks-global"></div>')
        $(css_identity + block_id).
          before($('<div class="spb-controls"></div>'))

        // Skip code for non popup pages.
        class_exists = $('#' + spb_popup_id).
          hasClass('simple-popup-blocks-global')
        if (!class_exists) {
          return true
        }
        // Minimize button wrap
        if (values.minimize === "1") {
          $("#"+spb_popup_id + " .spb-controls").
            prepend($('<button type="button" aria-label="Dismiss modal" class="' + modal_minimize_class + ' spb_minimize">&minus;</button>'))
          $('.' + modal_class).
            before($('<button type="button" aria-label="Open modal" class="' + modal_minimized_class + ' spb_minimized">&plus;</button>'))
        }
        // Close button wrap
        if (values.close == 1) {
          $("#"+spb_popup_id + " .spb-controls").
            prepend($('<button type="button" aria-label="Close modal" class="' + modal_close_class + ' spb_close">&times;</button>'))
        }
        // Overlay
        if (values.overlay == 1) {
          $('.' + modal_class).addClass('spb_overlay')
        }
        // Inject layout class.
        switch (values.layout) {
          // Top left.
          case '0':
            $(layout_class).addClass('spb_top_left')
            $(layout_class).css({
              'width': values.width,
            })
            break
          // Top right.
          case '1':
            $(layout_class).addClass('spb_top_right')
            $(layout_class).css({
              'width': values.width,
            })
            break
          // Bottom left.
          case '2':
            $(layout_class).addClass('spb_bottom_left')
            $(layout_class).css({
              'width': values.width,
            })
            break
          // Bottom right.
          case '3':
            $(layout_class).addClass('spb_bottom_right')
            $(layout_class).css({
              'width': values.width,
            })
            break
          // Center.
          case '4':
            $(layout_class).addClass('spb_center')
            $(layout_class).css({
              'width': values.width,
			  'margin-left': -values.width / 2
            })
            break
          // Top Center.
          case '5':
            $(layout_class).addClass('spb_top_center')
			  $(layout_class).css({
			  'width': values.width,
			  })
            break
          // Top bar.
          case '6':
            $(layout_class).addClass('spb_top_bar')
            $(layout_class).css({})
            break
          // Right bar.
          case '7':
            $(layout_class).addClass('spb_bottom_bar')
            $(layout_class).css({})
            break
          // Bottom bar.
          case '8':
            $(layout_class).addClass('spb_left_bar')
            $(layout_class).css({
              'width': values.width,
            })
            break
          // Right bar.
          case '9':
            $(layout_class).addClass('spb_right_bar')
            $(layout_class).css({
              'width': values.width,
            })
            break
        }
      if (show_model === true) {
        // Automatic trigger with delay
        if (values.trigger_method == 0 && values.delay > 0) {
          delays = values.delay * 1000
          $(css_identity + block_id).show()
          $('.' + modal_class).delay(delays).fadeIn('slow')
            if (values.overlay == 1) {
              setTimeout(stopTheScroll, delays)
            }
        }
        // Automatic trigger without delay
        else if (values.trigger_method == 0) {
            $('.' + modal_class).show()
            $(css_identity + block_id).show()
            if (values.overlay == 1) {
              stopTheScroll()
            }
        }
        // Manual trigger
        else if (values.trigger_method == 1) {
          $(document).on('click', values.trigger_selector, function (e) {
            $('.' + modal_class).show()
            $(css_identity + block_id).show()
            if (values.overlay == 1) {
              stopTheScroll()
            }
            return false;
          })
        }
        // Browser close trigger
        else if (values.trigger_method == 2) {
          $(_html).mouseleave(function (e) {
            // Trigger only when mouse leave on top view port
            if (e.clientY > 20) { return }
            // Trigger only once per page
            if (!browser_close_trigger) { return }
            browser_close_trigger = false
            $('.' + modal_class).show()
            $(css_identity + block_id).show()
            if (values.overlay == 1) {
              stopTheScroll()
            }
          })
        }
        }

        // Trigger for close button click
        $('.' + modal_close_class).click(function () {
          $('.' + modal_class).hide()
          startTheScroll()
        })
        // Trigger for minimize button click
        $('.' + modal_minimize_class).click(function () {
          $('.' + modal_class).hide()
          startTheScroll()
          $('.' + modal_minimized_class).show()
        })
        // Trigger for minimized button click
        $('.' + modal_minimized_class).click(function () {
          $('.' + modal_class).show()
          $(css_identity + block_id).show()
          if (values.overlay == 1) {
            stopTheScroll()
          }

        // Hide the minimized button.
        if (show_minimized_button == 0) {
          $('.' + modal_minimized_class).hide()
        }

        })
        // Trigger for ESC button click
        if (values.enable_escape == 1) {
          $(document).keyup(function (e) {
            if (e.keyCode == 27) { // Escape key maps to keycode `27`.
              $('.' + modal_class).hide()
              startTheScroll()
              $('.' + modal_minimized_class).show()
            }
          })
        }

      // Hide the minimized button.
      if (show_minimized_button == 0) {
        $('.' + modal_minimized_class).hide()
      }

      }) // Foreach end.
    }
  } // document.ready end.

  // Remove the scrolling while overlay active
  function stopTheScroll () {
    $('body').css({
      'overflow': 'hidden',
    });

    $('.simple-popup-blocks-global input:text').focus();
  }

  // Enable the scrolling while overlay inactive
  function startTheScroll () {
    $('body').css({
      'overflow': '',
    })
  }

  // Creating cookie
  function createCookie (name, value, days) {
    if (days > 0) {
      var date = new Date()
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000))
      var expires = '; expires=' + date.toGMTString()
    }
    else {
      var expires = ''
    }
    document.cookie = name + '=' + value + expires + '; path=/'
  }

  // Reading cookie
  function readCookie (name) {
    var nameEQ = name + '='
    var ca = document.cookie.split(';')
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i]
      while (c.charAt(0) == ' ') {
        c = c.substring(1, c.length)
      }
      if (c.indexOf(nameEQ) == 0) {
        return c.substring(nameEQ.length, c.length)
      }
    }
    return null
  }

})(jQuery, Drupal, once)
