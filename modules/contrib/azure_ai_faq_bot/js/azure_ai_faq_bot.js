(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.azureAIFAQChatbot = {
        attach: function (context, settings) {
            // Ensure the behavior is attached only once per page load.
            if (!context.querySelector('#azure-ai-faq-bot-webchat')) {
                return;
            }

            // Fetch the token from the server.
            fetch(Drupal.url('azure-ai-faq-bot/token'))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.token) {
                        throw new Error('Token not found in response');
                    }
                    console.log("Direct Line Token fetched");
                    window.WebChat.renderWebChat({
                        directLine: window.WebChat.createDirectLine({ token: data.token }),
                    }, document.getElementById("azure-ai-faq-bot-webchat"));
                })
                .catch(err => console.error("Failed to fetch token", err));
        }
    };

})(jQuery, Drupal);