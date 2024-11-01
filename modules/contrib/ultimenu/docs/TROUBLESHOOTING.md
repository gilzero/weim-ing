***
***

# <a name="troubleshooting"> </a>TROUBLESHOOTING
Whenever a menu item is removed/disabled, the relevant region will be removed.
If you manually copy/store them in theme .info, regions will always be visible,
which is another case.

Dynamic region is indeed removed, but system now displays your written regions.
However you can force disabling unwanted Ultimenu regions via UI if so required,
altering the system.

Make sure to clear the cache to see the new regions.

## WARNING!
**Do not add Ultimenu blocks (mega menus) into Ultimenu regions, else broken.**


## KNOWN ISSUES / LIMITATIONS
Before creating regions via Ultimenu UI, plan and decide the best route.

* Changing Menu item titles will remove its regions. The same thing happens if
  you change region keys via theme.info.yml. The only difference is changing
  regions via UI vs. via code editor on theme.info.yml. Both are equally prone
  to mistakes due to carelessness. Only via UI it is more surprising as much as
  more convenient.

  **Solutions:**
  + Don't allow editors to change menu titles (Administer menus and menu items).
    They are for site builders, not editors. Or inform them accordingly.
  + Carefully craft titles like a themer creating region keys. Abide by design.
  + Avoid changing menu titles once setup. Change Page title instead.
  + **Best of all**, use the provided safer and more permanent region names,
    regardless ugly. Especially when creating for clients or multilingual sites.
    Region key is more to machine than human:
      1. Use shortened UUID, not TITLE, for Ultimenu region key (deprecated)
      2. Use shortened HASH, not TITLE, for Ultimenu region key (takes
         precedence over previous option)

* If a menu item is deleted or disabled, the related Ultimenu region is deleted.
* Changing region key from TITLE to UUID/ HASH will reset related regions and
  blocks. Simply re-assign blocks to get them back.
* With a mix of (non-)ajaxified regions, checking whether a region is empty or
  not is defeating the purpose of ajaxified regions, to gain performance.
  **Solutions:**
  One accessible block, public or private (relevant to the current user), must
  be provided in the least regardless of complex visibility by paths or roles.
  A little trade off on your end.

At Ultimenu 3.x, we will likely enforce hashed region keys to avoid this issue
in the first place. Until then, please accept its current limitations.


## CURRENT DEVELOPMENT STATUS
Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is provided, running `drush cr` during
DEV releases should fix most issues as we add new services, or change things.

If you don't drush, before any module update, always open:

[Performance](/admin/config/development/performance)

And so you are ready to hit **Clear all caches** if any issue.
Only at worst case, know how to run https://drupal.org/project/registry_rebuild
safely.
