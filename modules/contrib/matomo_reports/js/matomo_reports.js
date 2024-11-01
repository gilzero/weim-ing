(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.MatomoReportsBehavior = {
    attach(context, settings) {
      // can access setting from 'drupalSettings';
      const pkUrl = drupalSettings.matomo_reports.matomoJS.url;
      const queryString = `${drupalSettings.matomo_reports.matomoJS.query_string}&jsoncallback=?`;
      console.log(queryString);
      const header =
        "<table class='sticky-enabled sticky-table'><tbody></tbody></table>";
      // Add the table and show "Loading data..." status message for long running requests.
      $('#matomopageviews').html(header);
      $('#matomopageviews > table > tbody').html(
        `<tr><td>${Drupal.t('Loading data...')}</td></tr>`,
      );
      // Get data from remote Matomo server.
      $.getJSON(`${pkUrl}index.php?${queryString}`, function (data) {
        let item = '';
        $.each(data, function (key, val) {
          item = val;
        });
        let pkContent = '';
        if (item !== '') {
          if (item.nb_visits) {
            pkContent += `<tr><td>${Drupal.t('Visits')}</td>`;
            pkContent += `<td>${item.nb_visits}</td></tr>`;
          }
          if (item.nb_hits) {
            pkContent += `<tr><td>${Drupal.t('Page views')}</td>`;
            pkContent += `<td>${item.nb_hits}</td></tr>`;
          }
        }
        // Push data into table and replace "Loading data..." status message.
        if (pkContent) {
          $('#matomopageviews > table > tbody').html(pkContent);
        } else {
          $('#matomopageviews > table > tbody > tr > td').html(
            Drupal.t('No data available.'),
          );
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
