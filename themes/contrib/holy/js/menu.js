/**
 * @file
 * Support for collapse menu item on mobile screen.
 */

(function ($, Drupal) {
  $('#menu .block-menu ul > li:has(ul)').each(function () {
    $(this).append("<span></span>");
  });

  $('#menu .block-menu li.active-trail > span').each(function () {
    $(this).prev().addClass("open");
    $(this).addClass("open");
  });

  $("#menu .block-menu ul > li > span").click(function () {
    if($(this).prev().css('display') == 'none'){
      $($(this).prev()).slideDown('fast', function () {
        $(this).next().addClass("open");
        $(this).addClass("open");
      });
    } else {
      $($(this).prev()).slideUp('fast', function () {
        $(this).next().removeClass("open");
        $(this).removeClass("open");
      });
    }
  });
})(window.jQuery, window.Drupal);
