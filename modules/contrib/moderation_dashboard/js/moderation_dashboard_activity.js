// eslint-disable-next-line func-names
(function ($, Drupal, once) {
  Drupal.behaviors.moderationDashboardActivity = {
    attach(context, settings) {
      const $activity = $('.moderation-dashboard-activity');

      // eslint-disable-next-line func-names
      once(
        'moderation-dashboard-activity',
        '.moderation-dashboard-activity',
        context,
        // eslint-disable-next-line func-names
      ).forEach(function () {
        /* global Chart */
        if (
          $activity.length &&
          settings.moderation_dashboard_activity &&
          Chart
        ) {
          const defaultActivityChartHeight = 500;
          let activityChartHeight =
            16 * settings.moderation_dashboard_activity.labels.length;

          if (activityChartHeight < defaultActivityChartHeight) {
            activityChartHeight = defaultActivityChartHeight;
          }
          const $canvas = $(
            $(`<canvas width="500" height="${activityChartHeight}"></canvas>`),
          );
          $activity.append($canvas);

          // eslint-disable-next-line no-new
          new Chart($canvas, {
            type: 'bar',
            data: settings.moderation_dashboard_activity,
            options: {
              indexAxis: 'y',
            },
          });
        }
      });
    },
  };
})(jQuery, Drupal, once);
