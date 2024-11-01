(function ($, Drupal, drupalSettings) {
  Drupal.AjaxCommands.prototype.viewsShowMore = function (ajax, response) {
    const selector = response.selector || ajax.wrapper;
    const $wrapper = $(selector);
    const method = response.method || ajax.method;
    const appendAt = response.append_at || '';
    const effect = ajax.getEffect(response);
    const settings = response.settings || ajax.settings || drupalSettings;
    const currentViewId = selector.replace('.js-view-dom-id-', 'views_dom_id:');
    const focusable = [
      'input:not([type="hidden"]):not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      'a[href]',
      'button:not([disabled])',
      '[tabindex]:not(slot)',
      'audio[controls]',
      'video[controls]',
      '[contenteditable]:not([contenteditable="false"])',
      'details>summary:first-of-type',
      'details',
    ].join(',');

    // eslint-disable-next-line jquery/no-parse-html
    let $newContent = $($.parseHTML(response.data, document, true));
    if ($newContent.length) {
      $newContent = Drupal.theme(
        'ajaxWrapperNewContent',
        $newContent,
        ajax,
        response,
      );

      // Get the existing ajaxViews object.
      const view = Drupal.views.instances[currentViewId];

      Drupal.detachBehaviors($wrapper.get(0), settings);
      once.remove('ajax-pager', view.$view);
      once.remove('exposed-form', view.$exposed_form);

      // Set up our default query options. This is for advance users that might
      // change there views layout classes. This allows them to write their own
      // jquery selector to replace the content with.
      // Provide sensible defaults for unordered list, ordered list and table
      // view styles.

      const headerSelector = response.options.header_selector;
      if (headerSelector && settings.header) {
        $(headerSelector).html(settings.header);
      }
      const footerSelector = response.options.footer_selector;
      if (footerSelector && settings.footer) {
        $(footerSelector).html(settings.footer);
      }

      const contentSelector =
        appendAt && !response.options.content_selector
          ? `.view-content ${appendAt}`
          : response.options.content_selector || '.view-content';
      const pagerSelector =
        response.options.pager_selector || '.pager-show-more';

      // Save first new item to use later.
      const $firstNewItem = $newContent
        .find(contentSelector)
        .children()
        .first();

      // Immediately hide the new content if we're using any effects.
      if (
        effect.showEffect !== 'show' &&
        effect.showEffect !== 'scrollToggle'
      ) {
        $newContent.find(contentSelector).children().hide();
      }

      const $contentArea = $wrapper.find(contentSelector);

      // Scrolling effect.
      if (
        effect.showEffect === 'scroll_fadeToggle' ||
        effect.showEffect === 'scrollToggle'
      ) {
        // Get old content height.
        const oldHeight = $contentArea.addClass('clearfix').height();

        // Get content count.
        const oldItems = $contentArea.children().length;
        const newItems = $newContent.find(contentSelector).children().length;

        // Calculate initial new height.
        const newHeight =
          oldHeight + Math.ceil((oldHeight / oldItems) * newItems);

        // Set initial new height for scrolling.
        if (effect.showEffect === 'scroll_fadeToggle') {
          $contentArea.height(newHeight);
        }

        // Get offset top for scroll.
        const positionTop =
          $contentArea.offset().top + oldHeight - response.scroll_offset;

        // Finally Scroll.
        // eslint-disable-next-line jquery/no-animate
        $('html, body').animate({ scrollTop: positionTop }, effect.showSpeed);
      }

      // Update the pager
      $wrapper.find(pagerSelector).replaceWith($newContent.find(pagerSelector));

      // Add the new content to the page.
      $contentArea[method]($newContent.find(contentSelector).children());

      if (
        effect.showEffect !== 'show' &&
        effect.showEffect !== 'scrollToggle'
      ) {
        if (effect.showEffect === 'scroll_fadeToggle') {
          effect.showEffect = 'fadeIn';
        }
        $contentArea
          .children(':not(:visible)')
          [effect.showEffect](effect.showSpeed);
        $contentArea.queue(function (next) {
          // eslint-disable-next-line jquery/no-css
          $(this).css('height', 'auto');
          next();
        });
      }

      // Move focus to first focusable item in new content.
      // If there are no focusable items, focus on the first container.
      if ($firstNewItem.find(focusable).length) {
        $firstNewItem.find(focusable).first().focus();
      } else {
        $firstNewItem.attr('tabindex', '0').focus();
      }

      // Additional processing over new content.
      // eslint-disable-next-line jquery/no-clone
      $wrapper.trigger('viewsShowMore.newContent', $newContent.clone());

      Drupal.attachBehaviors($wrapper.get(0), settings);
    }
  };
})(jQuery, Drupal, drupalSettings);
