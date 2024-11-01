/**
 * @file
 * Suport for collapse search block.
 */

(function ($, Drupal) {
  $("#header .block-search .search-open").click(function () {
    $(this).parent().addClass("open");
  });

  $("#header .block-search .search-close").click(function () {
    $(this).parent().parent().removeClass("open");
  });

  $(document).on('keyup',function(evt) {
    if (evt.keyCode == 27) {
      $("#header .block-search").removeClass("open");
    }
  });

})(window.jQuery, window.Drupal);
