### Overview

Moderation Dashboard provides a per-user dashboard that contains useful blocks
related to managing moderated content.

This module is meant as a jumping-off point for administrators to customize the
dashboard to fit their content editor's needs. As a result, no update hooks will
be provided between releases as users are expected to change the module's
default configuration.

Since the dashboard is built entirely with Page Manager and Views, everything
you see can be configured via the UI! Feel free to go crazy.

### Requirements

#### 1.x Branch module requirements
* Content Moderation
* Node
* Views
* Page Manager
* Panels

#### 2.x Branch module requirements
* Content Moderation
* Node
* Views
* Layout Builder

For the chart.js feature, follow either of the options:
1. Download chart.js library using composer or manually
   from https://github.com/chartjs/Chart.js/releases/tag/v3.9.1 placing
   inside libraries/chartjs/ (Downloading via composer is recommended.)
2. Use the CDN option.

**How to download chart.js library using composer:**
1. Add following code inside `repositories` section in your
   project's `composer.json`
```
"chart.js": {
    "type": "package",
    "package": {
        "name": "nnnick/chartjs",
        "version": "v4.4.1",
        "type": "drupal-library",
        "dist": {
           "dist": {
              "url": "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js",
              "type": "file"
        }
    }
}
```
2. If not added already, please add following code under `installer-paths`
   in `composer.json` (Add `web` or `docroot` according to your structure)
```
"web/libraries/{$name}": [
   "type:drupal-library",
]
```
3. Then run the command `composer require nnnick/chartjs`.
4. It should automatically download and place the `chartjs` library inside
   `web/libraries` or `docroot/libraries` folder.

For security, it's always safer to have a local copy vs external resource.

### Customization

#### 1.x Branch
To customize the dashboard, all you need to do is enable the Views UI and
Page Manager UI modules!

For convenience, you can go straight to the Page Manager configuration page
at /admin/structure/page_manager/manage/moderation_dashboard/page_variant__moderation_dashboard-panels_variant-0__content

Page Manager is a power-house, and will let you add, remove, configure,
and re-order any block normally available in Drupal. Additionally, you
can add different Page Variants to the Dashboard and configure their
Selection Criteria so that different roles see different dashboards. Go wild!

#### 2.x Branch
To customize the dashboard, all you need to do is enable the Views UI!

For convenience, you can go straight to the User Account Display configuration
page at /admin/config/people/accounts/display/moderation_dashboard
Here you can edit the layout via Layout Builder.
