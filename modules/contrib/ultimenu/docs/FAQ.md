***
***

# <a name="faq"> </a>FAQ
Check out [*TROUBLESHOOTING*](#troubleshooting) for known issues.
Check out [Ultimenu](https://www.drupal.org/project/issues/ultimenu) project
issues for more issues not covered here.

### Q: *How can I order the blocks within the region?*
### A:
* `/admin/structure/block`
* Find your *Ultimenu:main:REGION*, arrange (drag and drop) blocks per
  region like usual.

### Q: *How to arrange blocks and sub-menu links?*
### A:
* Visit `/admin/structure/block`,
* Find *Ultimenu: Main navigation* under any wide region, normally header,
* Click *Configure*, or here `/admin/structure/block/manage/ultimenumainnavigation`
* Enable *Render submenu*, choose *Submenu position*.
* Alternatively just use Drupal menu blocks for submenus
  ([#2631468](https://drupal.org/node/2631468)), and rearrange via Drupal block
  UI as usual.

### Q: *How to add sub-menu blocks?*
### A:
* Basically, all you need is place the new clone of existing Menu, and adjust
  its *Menu levels* when configuring, and embed it inside an Ultimenu region.
  If you want more features than what core provides, consider
  [Menu block](https://www.drupal.org/project/menu_block).
* Alternatively use the provided *Render sub-menu* option when editing the
  Ultimenu block via `/admin/structure/block` for simple needs, and edit its
  actual menu item (e.g.: `/admin/structure/menu/item/1/edit`) and check
  `Show as expanded`.

### Q: *How to add icon to a menu title?*
### A:
* `/admin/structure/menu` or `/admin/structure/menu/manage/main`
* Find *Edit* for each menu item, and under *Menu link title*, put, e.g.:

    `icon-mail|Contact us`

  (**Note** you can change `icon-mail` anytime, but cannot just change
  "`Contact us`" into "`Contact`" without losing your regions. Unless you
  choose *Use shortened HASH, not TITLE, for Ultimenu region key* under
  `Ultimenu Goodies` at `/admin/structure/ultimenu`.

### Q: *I lost my regions overnight on a multingual site, or even simple site*
### A:
* Check out [*Multilingual site*](#multilingual) section below.
* Choose *Use shortened HASH, not TITLE, for Ultimenu region key* under
  *Ultimenu Goodies* at `/admin/structure/ultimenu`.
* Do not let content editors change menu titles, unless using `Use shortened
  HASH, not TITLE, for Ultimenu region key`. Menus are not for content
  editors. They are for site builders, and admin level.

### Q: *How to create columns of the menu items instead of all in a row?*
### A:
* Not immediately available, custom works required. Check out
  [Widget](https://www.drupal.org/project/widget) with
  [Block class](https://www.drupal.org/project/block_class),
  [Radix](https://www.drupal.org/project/radix),
  [Bootstrap](https://www.drupal.org/project/bootstrap),
  [Bootstrap layouts](https://www.drupal.org/project/bootstrap_layouts), or use
  custom CSS via your themes for simple needs.
  For advanced needs, consider
  [GridStack](https://www.drupal.org/project/gridstack) (only reasonable to have
  unique layouts for massive blocks with entities -- paragraphs, media, etc.,
  views, slideshows, etc., likely less useful for most regular sites, though).

### Q: <a name="multilingual"> </a> *Multilingual?*
### A:
**i18n with Entity translation integration**

Ultimenu works great if the region is not language dependent, that is using the
MLID (D7), or UUID/ HASH (D8) as the region key.

These region keys are ugly. However as they are more to machine than human,
nothing to worry about. For human, hence themers, relevant CSS classes can be
enabled by options.

Check out [this screenshot](https://www.drupal.org/files/issues/store-ultimenu-regions-into-theme-info.png).
If Entity translation (D7) keeps one node for multiple languages, Ultimenu keeps
one region for multiple languages. D8 already supports entity translation.

* `/admin/structure/ultimenu`

  Check: *Use Hash, not title for Ultimenu region key*, because the title is
  language dependent which will cause issue with different languages.

* `/admin/structure/menu/manage/[MENU-NAME]/edit`

  Check: option #2 -- `Translate and Localize`.

* `/admin/structure/menu/item/[MLID]/edit`

  Keep *language neutral* to localize the menu title.


### Q: *Why another mega menu?*
### A:
I tried one or two, not all, and read some, but found no similar approach.
Unless I missed one. Please file an issue if any similar approach worth a merge.

### Q: *How can you help?*
### A:
Please consider helping in the issue queue, provide improvement, or helping with
documentation. Thanks!
