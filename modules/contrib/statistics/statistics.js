/**
 * @file
 * Statistics functionality.
 */

(function _($, drupalSettings) {
  setTimeout(() => {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.statistics.url,
      data: drupalSettings.statistics.data,
    });
  });
})(jQuery, drupalSettings);
