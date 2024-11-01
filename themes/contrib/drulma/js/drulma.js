/**
 * @file
 * Drulma js behavior.
 */

(function (Drupal) {
  function whenAvailable(name, callback) {
    // Store the interval id
    const intervalId = window.setInterval(function () {
      if (window[name]) {
        // Clear the interval id
        window.clearInterval(intervalId);
        // Call back
        callback(window[name]);
      }
    }, 10);
  }

  /**
   * Parse the parts added with ajax.
   */
  Drupal.behaviors.DrulmaJS = {
    attach(context, settings) {
      // The BulmaJS library already traverses the DOM on document.
      if (context !== document) {
        whenAvailable('Bulma', function (Bulma) {
          // TODO: Traverse from the same element instead
          // of the parent when the following issue is fixed.
          // https://github.com/VizuaaLOG/BulmaJS/issues/95
          Bulma.parseDocument(context.parentElement);
        });
      }
    },
  };
})(Drupal);
