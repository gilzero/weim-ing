(function($, Drupal, once) {
    "use strict";
    var whatsappInExecuted = false;
    Drupal.behaviors.whatsappIn = {
        attach: function (context, settings) {
            if (!whatsappInExecuted) {
                var phoneNumber = settings.phone_number;
                var modulePath = drupalSettings.path.baseUrl;
                var html = '<div class="share-icon"><a href="https://wa.me/' + phoneNumber + '" title="Open WhatsApp" target="_blank"><img width="40" height="40" src="' + modulePath + 'modules/contrib/whatsapp_in/icon.png"></a></div>';
                $(once('whatsapp','#header', context)).append(html);

                if ($('form.user-pass-reset', context).length) {
                    $('form.user-pass-reset', context).addClass('container');
                }
                whatsappInExecuted = true;
            }
        }
    };
})(jQuery, Drupal, once);