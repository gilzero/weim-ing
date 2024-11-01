/**
 * @file
 * File type_tray.js.
 */

(function ($, Drupal) {

  // Gets the title and description from a teaser and stores it.
  function storeHaystackOnTeaser(i, teaser) {
    const haystack =
      $(teaser)
        .find('[data-type-tray="title"], .type-tray__short-desc')
        .text()
        .toUpperCase();
    $(teaser).data('haystack', haystack);
  }

  // Shows / Hide teaser if needle is not found.
  function toggleTeaser(teaser, needle) {
    const needleFound = $(teaser).data('haystack').indexOf(needle) > -1;
    $(teaser).toggle(needleFound);
  }

  // This is a very basic in-page filtering script that hides all teasers
  // whose type title does not contain the search string entered by the user.
  Drupal.behaviors.TypeTraySearchBehavior = {
    attach: function (context, settings) {
      const $search_input = $('input[data-type-tray="search-box"]', context);
      if ($search_input.length === 0) {
        return;
      }

      const teasers = $('[data-type-tray="teaser-wrapper"]', context);

      // Storing text to search against on teasers.
      teasers.map(storeHaystackOnTeaser);

      // Search on haystacks from teasers to hide or show based on search value.
      function searchForTypes(event) {
        const needle = event.target.value.toUpperCase();
        teasers.map((index) => toggleTeaser(teasers[index], needle));
      }

      // On key up, show or hide teasers.
      $search_input.on('keyup', Drupal.debounce(searchForTypes, 250));
    }
  };
})(jQuery, Drupal);
