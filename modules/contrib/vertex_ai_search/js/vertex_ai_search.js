(function (Drupal, once) {
  Drupal.behaviors.vais = {
    attach(context) {
      once('widget', '#edit-keys', context).forEach(function (element) {
        element.classList.add('vais-autocomplete');
      });
    },
  };
})(Drupal, once);
