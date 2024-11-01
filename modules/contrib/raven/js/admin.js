/**
 * @file
 * Sends test message to Sentry.
 */

((Drupal, Sentry) => {
  const jsButton = document.getElementById('edit-raven-js-test');
  if (Sentry && jsButton) {
    jsButton.disabled = false;
    jsButton.classList.remove('is-disabled');
    jsButton.addEventListener('click', (event) => {
      event.preventDefault();
      const id = Sentry.captureMessage(
        Drupal.t('Test message @time.', { '@time': new Date() }),
      );
      const div = document.createElement('div');
      div.innerHTML = Drupal.t('Message sent as event %id.', { '%id': id });
      jsButton.parentNode.insertBefore(div, jsButton.nextSibling);
    });
  }
  const phpButton = document.getElementById('edit-raven-php-test');
  if (phpButton) {
    const displayLog = (logs) => {
      logs.forEach((log) => {
        const div = document.createElement('div');
        div.innerHTML = Drupal.t('Logged @level: @message', {
          '@level': log.level,
          '@message': log.message,
        });
        phpButton.parentNode.insertBefore(div, phpButton.nextSibling);
      });
    };
    phpButton.disabled = false;
    phpButton.classList.remove('is-disabled');
    phpButton.addEventListener('click', (event) => {
      event.preventDefault();
      fetch(Drupal.url('raven/test'), {
        method: 'POST',
        // The route requires this non-safelisted MIME type for purposes of
        // blocking cross-origin requests.
        headers: { 'Content-Type': 'application/json' },
      })
        .then((response) => response.json())
        .then((data) => {
          displayLog(data.log);
          const div = document.createElement('div');
          div.innerHTML = Drupal.t(
            data.id ? 'Message sent as event %id.' : 'Send failed.',
            {
              '%id': data.id,
            },
          );
          phpButton.parentNode.insertBefore(div, phpButton.nextSibling);
        });
    });
  }
})(Drupal, window.Sentry);
