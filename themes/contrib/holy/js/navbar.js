/**
 * @file
 * Suport for menu collapse with navbar icon.
 */

(function ($, Drupal) {
  $(".icon-menu").click(function () {
    if ($('body').hasClass('menu-open')) {
      $("body").removeClass("menu-open");
    }
    else {
      $("body").addClass("menu-open");
    }
  });
})(window.jQuery, window.Drupal);
