# Matomo Reports

This module adds a Matomo reports section and imports key traffic information
from your Matomo server.

Project homepage: https://drupal.org/project/matomo_reports

Issues: https://drupal.org/project/issues/matomo_reports

## Documentation

### Reports
This module provides some of the Matomo reports directly in your Drupal
site. Just follow the installation instructions and go to
admin/reports/matomo_reports.

### Multisite
Matomo reports will show statistics of every site the token_auth has view
permissions on the matomo server. Administrators can limit access to only
allowed sites.

### Block
A Matomo page report block is available for in-page statistics. You must
enable the block and place it in the region you desire.

### Matomo Web Analytics
[Matomo Web Analytics](https://drupal.org/project/matomo) is not a
dependency, but Matomo is required to track your site.

## Installation

 * composer require drupal/matomo_reports

## Configuration

 * Add your Matomo reports token_auth either globally
(Administration » Configuration » System » Matomo Reports) or individually
(in each user profile)

## Maintainer

* Shelane French - [shelane](https://www.drupal.org/user/2674989)
