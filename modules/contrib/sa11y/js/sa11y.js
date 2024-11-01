(function sa11yScript(Drupal) {
  Drupal.behaviors.sa11y = {
    attach: function (context, settings) {
      context = context || document;
      Sa11y.Lang.addI18n(Sa11yLangEn.strings);
      const sa11y = new Sa11y.Sa11y({
        checkRoot: "div.dialog-off-canvas-main-canvas",
      });
    },
  };
})(Drupal);
