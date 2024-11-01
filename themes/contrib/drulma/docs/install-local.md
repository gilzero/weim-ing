# Install a local version of the Bulma library for customization

By default Drulma is configured to download the Bulma CSS library and BulmaJS
from a CDN so the theme doesn't require additional steps to be displayed
correctly. This way including Bulma inside Drulma source code is avoided.

Loading from a CDN is fine but it has limitations and drawbacks.
Once Bulma is hosted locally it allows for it to be compiled and configured
so it can be served adjusted to the requirements.

## First option: using Libraries Provider

One choice to be able to configure Bulma is to use the
[Libraries provider](https://www.drupal.org/project/libraries_provider) module.
After installing the module and configuring
[asset packagist](https://asset-packagist.orocrm.com) (so the
Bulma libraries gets installed at `/web/libraries/bulma` from the root of the
project). The actual library can be required with the following command.

```
composer require npm-asset/bulma
```

After that the libraries provider UI will have the option of using the local
version.

When managing a Drupal theme that uses a SASS library and provides
[the list of available variables to modify](https://git.drupalcode.org/project/drulma/tree/8.x-1.x/libraries_provider),
Libraries Provider can compile the SASS files using the
[sassphp](https://github.com/absalomedia/sassphp) extension.

Once the requirements are fulfilled the UI of Libraries Provider will give the
option to set new values for the SASS variables defined in Bulma overriding
the defaults.

## Second option: use the nodejs ecosystem (node-sass, gulp, etc)

NOTE: this option has not been tested by the Drulma maintainers,
it was suggested by a contributor. See the next issue for context.
https://www.drupal.org/project/drulma/issues/3133032

The steps in this guide enabled me to host all Drulma dependencies
locally, minimize the CSS/JS, avoid using any CDNs and shrink my Bulma CSS to
just 19 KB. It also shrinks BulmaJS from 100+ Kb to ~20 Kb with a custom fork
of BulmaJS that only builds the navbar, file, and dropdown components.

First, we have to create a subtheme of Drulma.

Next, override the drulma libraries.

Add the following lines to `mydrulmasubtheme.info.yml`:

```
libraries-override:
  # drulma
  drulma/global: false
  drulma/drulmajs: mydrulmasubtheme/drulmajs
  drulma/bulmajs: mydrulmasubtheme/bulmajs
  drulma/bulma: false
```
Next, `mydrulmasubtheme.libraries.yml`:

```
global:
  css:
    theme:
      css/style.min.css: {}

bulmajs:
  js:
    js/bulma.min.js: {} # only includes dropdown, file, navbar

drulmajs:
  js:
    js/drulma.min.js: {}
  dependencies:
    - mydrulmasubtheme/bulmajs
    - core/drupal
```

The js files are the three components used by Drulma. Ideally, they
would be combined into a single file as described in the bulmajs documentation,
but that is not resolved yet.
(https://github.com/VizuaaLOG/BulmaJS/issues/100)

Next, I installed bulma with npm. You will need to install npm somehow,
it won't be documented here.

This
[package.json can be used](https://gist.github.com/ptmkenny/07f2a8b98b8dcffea0581c52343a62cf#file-package-json)

Next make a `style.scss` file and put it in mydrulmasubtheme/sass/bulma_sass.

It could look like this:

```
@charset "utf-8";

// @import "node_modules/bulma/bulma.sass";
// import components individually
// TODO: eliminate unneeded components
@import "bulma_sass/utilities/_all";
@import "bulma_sass/base/_all";
@import "bulma_sass/elements/_all";
@import "bulma_sass/form/_all";
@import "bulma_sass/components/_all";
@import "bulma_sass/grid/_all";
@import "bulma_sass/layout/_all";

@import "../../contrib/drulma/css/bulma-overrides";
@import "../../contrib/drulma/css/drupal-overrides";
@import "../../contrib/drulma/css/tweaks";
```

To get this to work, symlink the bulma sass directory installed by
node (ln -s ../node_modules/bulma/sass/ bulma_sass) or adjust the path
accordingly.

Then you can compile with Gulp.


This
[gulpfile.js](https://gist.github.com/ptmkenny/07f2a8b98b8dcffea0581c52343a62cf#file-gulpfile-js)
was modified from the one provided with the Bootstrap SASS theme. To use the
gulpfile,
create a src directory in your drulma subtheme and then symlink the drulma_js
(ln -s ../../../contrib/drulma/js drulma_js).

This gulpfile uses UnCSS, which automatically removes CSS that is not used on
the site.
This makes the stylesheet very small but it means a style guide on your site
needs to be generated; otherwise, all the CSS will be stripped.
You should configure uncss to go to at least one page of each content type,
etc. on your website.

You can also use the Simple Styleguide module to easily generate a basic style
guide for your site.

## Improving the documentation.

This page is generated from the `/docs` folder in the Drulma repository.
Please provide merge request or patches to make it better.
