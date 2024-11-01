CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Recommended modules
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
 
 * GOV.UK is a Drupal 8/9/10/11 theme. The theme is not dependent on any core theme.
 * This theme utilises the GOV.UK FrontEnd node module and has Twig template
   files for the majority of GOV.UK styles, components and patterns.
   In no way will this meet 100% of you requirements, but it is a good start.
   You will still have to create/modify Twig files to get your required look & feel.
   See https://design-system.service.gov.uk

RECOMMENDED MODULES
-------------------

 * No extra module is required.
 
REQUIREMENTS
------------

 * No extra module is required.
 * Node.js >= V10.0 See https://nodejs.org
 * Gulp >= V4.0

INSTALLATION
------------

 * Install as usual, see
   https://www.drupal.org/docs/user_guide/en/extend-theme-install.html
 * cd to the themes directory eg. /themes/custom/govuk_theme
 * Issue the command 'npm build'. This will build all the required node
   modules into /themes/custom/govuk_theme/node_modules.
 * Install Gulp with 'npm install gulp'.
 * Issuing 'gulp' by its self (or 'gulp build') will compile the SASS files into the css folder.
 * Issuing 'gulp watch' will watch the SASS folder and compile any changes into the css folder.

CONFIGURATION
-------------

 * Configuration is available in Admin > Appearance.
 
SUB THEME
---------

 * Copy the folder SUB_THEME from web/themes/contrib/govuk_theme into web/themes/custom
   if the web/themes/custom folder does not exist then create it.
 * Rename web/themes/custom/SUB_THEME to the name of your theme eg. my_theme
 * Rename web/themes/custom/[my_theme]/govuk_subtheme.info.yml to [my_theme].info.yml
   Open [my_theme].info.yml and change the name parameter to the name of your theme eg. 'My Theme'
   Remove the line 'hidden: true'
 * Rename web/themes/custom/[my_theme]/govuk_subtheme.libraries.yml to [my_theme].libraries.yml
 * Open [my_theme].libraries.yml and under the libraries parameter
   change govuk_subtheme/global-styling to [my_theme]/global-styling.
   Also under libraries-override change govuk_subtheme/global-styling to [my_theme]/global-styling
 * Rename web/themes/custom/[my_theme]/config/install/govuk_subtheme.settings.yml to [my_theme].settings.yml
 * If you want to use SASS in this sub theme, from the sub theme folder run 'npm install'
 * Do a drush cr so Drupal will pick up this new theme.
 * Navigate to /admin/appearance and in the list of Uninstalled themes
   select 'Install and set as default' for this new sub theme. Ensure that the GOV.UK theme
   is the default theme before installing your new sub theme.

TROUBLESHOOTING
---------------

 * Theme is compatible and tested on IE9+, Opera, Firefox & Chrome browsers, so it won't make any troubles.
 * Support for IE8 is no longer a GDS requirement and is not supported by this theme.


MAINTAINERS
-----------

 * https://www.drupal.org/u/webfaqtory
