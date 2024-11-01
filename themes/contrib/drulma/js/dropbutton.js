(function ($, Drupal) {
  Drupal.theme.dropbuttonToggle = function (options) {
    // Add button class.
    return `<li class="dropbutton-toggle button"><button type="button"><span class="dropbutton-arrow"><span class="visually-hidden">${options.title}</span></span></button></li>`;
  };
})(jQuery, Drupal);
