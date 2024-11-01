(function (drupalSettings, Drupal, once) {
  // Taken from: https://stackoverflow.com/a/47932848
  function camelToDash(str){
    return str.replace(/([A-Z])/g, function($1){return "-"+$1.toLowerCase();});
  }

  let hcaptchaReady = false;
  window.drupalHcaptchaOnload = function() {
    hcaptchaReady = true;
    Drupal.behaviors.hcaptcha.attach();
  };

  if (!document.getElementById('hcaptcha-src') && drupalSettings.hcaptcha) {
    var el = document.createElement('script');
    el.type = 'text/javascript';
    el.src = drupalSettings.hcaptcha.src;
    el.id = 'hcaptcha-src';
    el.async = true;
    el.defer = true;
    document.getElementsByTagName('head')[0].appendChild(el);
  }

  const renderWidget = function (element) {
    const config = Object.fromEntries(
      Object.entries(element.dataset)
      .map(function (key_val) { return [camelToDash(key_val[0]), key_val[1]]; })
    );
    hcaptcha.render(element, config);
  }

  Drupal.behaviors.hcaptcha = {
    attach: function () {
      if (!hcaptchaReady) return;
      once('hcaptcha-rendered', '.h-captcha')
        .forEach(renderWidget); // Just use renderWidget as callback.
    }
  }
})(drupalSettings, Drupal, once);
