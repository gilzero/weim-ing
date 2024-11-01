/**
 * Override from the 'text' module to change markup.
 *
 * @see https://www.drupal.org/project/drupal/issues/3040302
 * */

(function ($, Drupal) {
  Drupal.behaviors.textSummary = {
    attach: function attach(context, settings) {
      $(context)
        .find('.js-text-summary')
        .once('text-summary')
        .each(function () {
          const $widget = $(this).closest('.js-text-format-wrapper');

          const $summary = $widget.find('.js-text-summary-wrapper');
          const $summaryLabel = $summary.find('label').eq(0);
          const $full = $widget.children('.js-form-type-textarea');
          let $fullLabel = $full.find('label').eq(0);

          if ($fullLabel.length === 0) {
            $fullLabel = $('<label></label>').prependTo($full);
          }

          const $link = $(
            Drupal.theme('textEditSummaryButton', Drupal.t('Hide summary')),
          );
          const $button = $link.find('button');
          let toggleClick = true;
          $link
            .on('click', function (e) {
              if (toggleClick) {
                $summary.hide();
                $button.html(Drupal.t('Edit summary'));
                $link.appendTo($fullLabel);
              } else {
                $summary.show();
                $button.html(Drupal.t('Hide summary'));
                $link.appendTo($summaryLabel);
              }
              e.preventDefault();
              toggleClick = !toggleClick;
            })
            .appendTo($summaryLabel);

          if ($widget.find('.js-text-summary').value === '') {
            $link.trigger('click');
          }
        });
    },
  };
  $.extend(Drupal.theme, {
    textEditSummaryButton: function textEditSummaryButton(title) {
      return `<span class="field-edit-link"> <button type="button" class="button is-link link link-edit-summary">${title}</button></span>`;
    },
  });
})(jQuery, Drupal);
