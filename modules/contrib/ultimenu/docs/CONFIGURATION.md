***
***
# <a name="configuration"> </a>CONFIGURATION
Ultimenu supports multiple mega menus for the header, sidebars, or footer.

Check out **OFF-CANVAS MENU** section below for off-canvas menus.
And repeat for the rest of mega menus accordingly. Only one off-canvas menu
can exist on a site. The rest are optional sidebars, footer mega menus.

**Please keep this in mind to avoid confusion**

Ultimenu will create two things:

* **blocks** based on Menu name. This is the mega menu itself aka ultimenu.
* **regions** based on enabled Menu items (item titles, if not using HASH).

These special blocks (mega menus) cannot be placed into these special regions!

## CONFIGURING ULTIMENU BLOCKS
* `/admin/structure/ultimenu`

  1. *Toggle Ultimenu blocks*, choose *Main navigation*, etc. **Save!**.
  2. *Toggle Ultimenu regions*. Once a menu is enabled and saved, dynamic
     regions will be available here to toggle. Only enabled regions (based on
     enabled menu items) will be visible at block/ context admin.
  3. *Toggle Ultimenu goodies*. Adjust the rest here.

* `/admin/structure/block`

   Jump to [**OFF-CANVAS MENU**](#offcanvas) below for off-canvases. Or
   do the following for non off-canvas:
   1. Hit *Place Block* at any region (Header, Footer, etc., except Ultimenu
      regions!).
   2. Search for *Ultimenu:* block, and hit *Place Block*. Edit + Save!
      This is the super Ultimenu block which contains other blocks added at step
      #3 below.
   3. Scroll to bottom. Search for `Ultimenu:..` regions,
      e.g.: `Ultimenu:footer: Home`, etc. Add other blocks to these Ultimenu
      regions one at a time.

To add more megamenus (sidebar, footer, etc), repeat this section for different
Menus.


## <a name="offcanvas"> </a>CONFIGURING OFF-CANVAS MENU
Since Ultimenu:3.0.3, any menus can be made off-canvases under one condition:  
* Enable **Offcanvas** option at the menu block.  

Useful for micro sites, etc. The old rule still applies, only one off-canvas
can exist on a page using the existing block visibility rules to avoid multiple
off-canvas menu on a page.

Change **Main navigation** to other menu names as needed.

1. `/admin/structure/ultimenu`

   + Enable **Main navigation** under **Ultimenu blocks**.  
     Leave the rest alone till you get a grasp and or need more mega menus.  
     **Save!**
   + Once saved, enable any relevant region under **Ultimenu regions**.  
     **Save!**

2. `/admin/appearance`

   Switch your theme temporarily to Bartik to see it working immediately with
   the provided default values to select from.

3. `/admin/config/development/performance`

   Clear cache, your theme needs to know the newly created regions.   

4. `/admin/structure/block`

   + Under **Header** or any header region like **Primary menu**, hit **Place
     block** button.
   + Search for **Ultimenu: Main navigation**, hit **Place block** button.
     This is the Ultimenu block container containing regions and blocks in one.
   + Fill out the rest of form. The required for now are **Off-canvas element**
     and **On-canvas element**. Use default values for Bartik or Olivero to
     begin with. You can leave them empty later, once done with **STYLING**
     section.
     **Save!**
   + Find the new **Ultimenu regions**, normally at the bottom.
   + Add any other block, except the **Ultimenu block** itself, to each newly
     created **region** prefixed with **Ultimenu:main** accordingly.
     **Save!**

### **Important!**

Do not add Ultimenu blocks (mega menus) into Ultimenu regions, else broken.
Watch for the repeated **Save**. It means it must be saved one at a time.
Check out **STYLING** section to understand more about off-canvas.
