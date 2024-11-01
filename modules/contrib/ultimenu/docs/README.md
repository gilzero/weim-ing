
# CONTENTS OF THIS FILE
Git page: https://git.drupalcode.org/project/ultimenu/-/tree/3.0.x/docs

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Styling](#styling)
 * [Updating](#updating)
 * [Troubleshooting](#troubleshooting)
 * [FAQ](#faq)
 * [Maintainers](#maintainers)

***
***
# <a name="introduction"> </a>INTRODUCTION
Ultimenu is the UltimatelyDeadSimple megamenu ever with dynamic region creation.

An Ultimenu block (the mega menu) is based on a menu.
Ultimenu regions are based on the menu items.

The result is a block contains regions containing blocks, as opposed to: a
region contains blocks.

The module manages the toggle of Ultimenu blocks, regions, and a skins library,
while leaving the management of block, menu and regions to Drupal.

At individual Ultimenu block, you can define a unique skin and the flyout
orientation.

You don't have to write regions in the theme .info, however you can always
permanently store resulting region definitions in it.

***
***
# <a name="requirements"> </a>REQUIREMENTS
* [Blazy 3.x](https://drupal.org/project/blazy) since 3.x to DRY for vanilla JS.
* Drupal core optional menu.module should be enabled.
* Drupal **Main navigation** like at Standard profile:
  **/admin/structure/menu/manage/main**

  If not, just have a menu with the same machine name **main**.

***
***
# <a name="recommended-modules"> </a>RECOMMENDED MODULES
* [Ajaxin](https://drupal.org/project/ajaxin)

  To have decent loading animations for AJAX contents within Ultimenu.

* [Widget](https://drupal.org/project/widget)

  To arrange blocks with layout within Ultimenu.

* [GridStack](https://drupal.org/project/gridstack)

  To have massive blocks with unique layouts for entities, views.

## RELATED MODULES
* [OM Maximenu](https://drupal.org/project/om_maximenu)
* [Megamenu](https://drupal.org/project/megamenu)
* [Superfish](https://drupal.org/project/superfish)
* [Menu Views](https://drupal.org/project/menu_views)
* [MuchoMenu](https://drupal.org/project/1077858)
* [Giga Menu](https://drupal.org/project/gigamenu)
* [Menu Minipanels](https://drupal.org/project/menu_minipanels)
* [Mega Dropdown](https://drupal.org/sandbox/ravigupta/1099796)
* [Menu Attach Block](https://drupal.org/project/menu_attach_block)


***
***
# <a name="installation"> </a>INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)

Be sure to read the entire docs and form descriptions before working with
Ultimenu to avoid headaches for just ~5-minute read.

Ultimenu is so simple that it might hurt. Once you tame it, you'll love it!


***
***
## FEATURES at 3.x
* Requires Blazy to DRY with vanilla JS, inline with core directions.
* Multiple off-canvas menus, aside from Main menu.
* Collapsible sub-menus.
* Carets can be enabled for desktop, and have configurable skins.
* Supports <nolink> (span), hashed link (#), non-clickable links.

***
***
## FEATURES at 2.x
* Optional vanilla JavaScript if Blazy is installed.
* Ajaxified Ultimenu regions, suitable for massive menu contents.
* Off-canvas menu, mobile only by default. Yet, configurable for both mobile and
  desktop under **Ultimenu goodies** section.
* Iconized menu titles.
* Iconized menu descriptions since 2.11.


## FEATURES
1. Multiple instance of Ultimenus based on system and menu modules.
2. Dynamic regions based on menu items which is toggled without touching .info.
3. Render menu description.
4. Menu description above menu title.
5. Add title class to menu item list.
6. Add mlid class to menu item list.
7. Add menu counter class.
8. Remove browser tooltip.
9. Use mlid, not title for Ultimenu region key.
10. Custom skins, or theme default "css/ultimenu" directory for auto discovery.
11. Individual flyout orientation: horizontal to bottom or top, vertical to
    left or right.
12. Pure CSS3 animation and basic styling is provided without assumption.
    Please see and override the ultimenu.css for more elaborate positioning,
    either left, centered or right to menu list or menu bar.
13. With the goodness of blocks and regions, you can embed almost anything:
    views, panels, blocks, menu_block, boxes, slideshow..., except a toothpick.

All 1-9 is off by default.
