***
***

# <a name="updating"> </a>UPDATING
## <a name="updating-3"> </a>UPDATING FROM 2.x To 3.x
Due to a need to access HTML element to fix mobile glitches in 3.0.2, and the
addition of new options to support multiple off-canvases, caret and hamburger
for any menus in 3.0.3, 3.0.2 - 3.0.3 introduced breaking changes:
* **3.0.2** -- Mostly the ancestor classes of Ultimenu blocks: body to html,
  with some clearer BEM. If you are customizing the styling, please update them
  accordingly. Be sure to press `F5` or `CTRL/CMD + R` to clear browser cache,
  and also clearing Drupal cache if changing Twig files at
  `/admin/config/development/performance`, or `drush cr`.
* **3.0.3** -- The introduction of few options at admin block such as
  **Use caret**, **Use as off-canvas**, **Always use hamburger** requires
  re-configuring and re-saving forms at:
  + Block admin (`/admin/structure/block`), find the Ultimenu block at header.
  + Ultimenu settings (`/admin/structure/ultimenu`).
    * The global option for off-canvas in desktop and mobile is moved into block
      admin as **Always use hamburger** to support multiple off-canvases.
    * A new option **Decouple/ treat Main menu like others** is provided to
      remove BC, and recommended to enable it. This transition hack will be
      removed at 4.x.

Re-configuring and re-saving the forms should bring your off-canvas back. A
temporary BC was provided, however re-saving is required, in case any misses.

See CR at https://www.drupal.org/node/3447576.

<pre>
Before 3.0.2                              x After 3.0.2

* `body.is-ultimenu-canvas`               x `html.is-ultimenu`
* `body.is-ultimenu-canvas--active`       x `html.is-ultimenu--active`
* `body.is-ultimenu-canvas--hover`        x `html.is-ultimenu--hover`
* `body.is-ultimenu-canvas--hiding`       x `html.is-ultimenu--hiding`
* `body.is-ultimenu-expanded`             x `html.is-ultimenu--expanded`
* `div.is-ultimenu-canvas-backdrop`       x `div.is-ultimenu__backdrop`
* `header.is-ultimenu-canvas-off`         x `header.is-ultimenu__canvas-off`
* `siblings.is-ultimenu-canvas-on`        x `siblings.is-ultimenu__canvas-on`
* `ul.ultimenu--hover`                    x `ul.is-ultihover`
* `li.is-ultimenu-item-expanded`          x `li.is-uitem-expanded`
* `a.is-ultimenu-active`                  x `a.is-ulink-active`
* `span.caret`                            x `span.ultimenu__caret`
* `caret::before`                         x `ultimenu__caret i::after`
* `button.is-ultimenu-button-active`      x `button.is-ubtn-active`
</pre>

Note `caret::before` vs `ultimenu__caret i::after`, which holds the up and down
arrows, this change is to support menu items without links and more complex icon
styling. The `ultimenu__caret` is now used consistently as click
handler with or without links. See `css/components/ultimenu.caret.css`.

## <a name="updating-2"> </a>UPDATING FROM 1.x To 2.x
If not updating from 1.x to 2.x, please ignore this section.

Ultimenu 2.x is a major rewrite to update for Drupal 8.6+, and add new features.
It may not be compatible with 1.x.
Ultimenu 2.x added few more services, so it may break the site temporarily
mostly due to the introduction of new services. If you do drush, this is no
issue. Running `drush cr`, `drush updb` and `drush cr` should do.

* Have backup routines.
* Test it out at a staging or DEV environment.
* Hit **Clear all caches** at
  [/admin/config/development/performance](/admin/config/development/performance)
  once this updated module is in place after `composer update`. This is
  important so that service or function changes do not interfere the update.
  Keep this page open, never re-load, and keep hitting that cute button if any
  issues.
* Run **/update.php**, or regular `drush cr`, `drush updb` and `drush cr`.

Note the recommended order above!
Read more [here](https://git.drupalcode.org/project/blazy/tree/docs/UPDATING.md?h=8.x-2.x)

The following are changes, in case you are updating from 1.x.


## NOTABLE CHANGES
- The flyouts no longer use `display:none`. Instead `visibility: hidden`. This
  should fix compatibility issues with lazy-loaded images without extra legs.
- Renamed **active-trail** LI class to **is-active-trail** to match core:
  https://www.drupal.org/node/2281785
- Renamed **js-ultimenu-** classes to **is-ultimenu__** so to print it in
  HTML directly not relying on JS, relevant for the new off-canvas menu.
- Added option to selectively enable ajaxified regions.
- Cleaned up CSS sample skins from old browser CSS prefixes. It is 2019.
- Added off-canvas menu to replace old sliding toggle approach.
- Split `ultimenu.css` into `ultimenu.hamburger.css` + `ultimenu.vertical.css`.
- Added support to have a simple iconized title, check out STYLING.


## FYI CHANGES
The following are just FYI, not really affecting the front-end, except likely
temporarily breaking the site till proper `drush cr` mentioned above.

1. UltimenuManager is split into 3 services: UltimenuSkin, UltimenuTool,
   UltimenuTree services for single responsibility, and to reduce complexity.
2. Ultimenu CSS files are moved from module **/skins** folder to **css/theme**.
3. Moved most logic to `#pre_render` to gain some performance.
4. Old `template_preprocess_ultimenu()` loop is also merged into `#pre_render`.
