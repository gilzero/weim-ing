***
***

# <a name="styling"> </a>STYLING
Please ignore any documentation if you are already an expert. This is for the
sake of documentation completion for those who might need it. If it is not you.

## Changes since 3.0.2
Due to a need to access HTML element to fix mobile glitches, 3.0.2 introduced
a breaking change. See **UPDATING FROM 2.x To 3.x** section under [UPDATING](#updating)
for details.

## Understanding off-canvas:
The off-canvas requires you to understand the HTML structure of your page. You
may want to edit your **html.html.twig** or **page.html.twig** later manually.
Press F12 at any browser, inspect element. The following is a simplified Bartik.

View at Git page if you don't have Markdown:
https://git.drupalcode.org/project/ultimenu/-/blob/3.0.x/docs/STYLING.md#understanding-off-canvas

Normally the template which should be edited is `page.html.twig`.

````
<html class="is-ultimenu">
  <body>
    <div id="page-wrapper">
      <div id="page">
        <!-- This element, out of canvas by default, can only exist once. -->
        <header id="header" class="is-ultimenu__canvas-off"></header>

        <!-- The elementse  will be pushed out of canvas once header is in. -->
        <div class="highlighted is-ultimenu__canvas-on"></div>
        <div class="featured-top is-ultimenu__canvas-on"></div>
        <div id="main-wrapper" class="is-ultimenu__canvas-on"></div>
        <div class="site-footer is-ultimenu__canvas-on"></div>
      </div>
    </div>
  </body>
</html>

````

## Alternative layout:
````
<html class="is-ultimenu">
  <body>
    <div class="l-page">
      <!-- This element, out of canvas by default, can only exist once. -->
      <header class="l-header is-ultimenu__canvas-off"></header>

      <!-- Only one element at the same level, put it into the container. -->
      <div class="l-main is-ultimenu__canvas-on">
        <div class="l-highlighted"></div>
        <div class="l-featured-top"></div>
        <div class="l-content"></div>
        <div class="l-footer"></div>
      </div>
    </div>
  </body>
</html>

````

## Another alternative layout:
````
<html class="is-ultimenu">
  <body>

    <!-- The page container is placed below header, the same rule applies. -->

    <!-- This element, out of canvas by default, can only exist once. -->
    <header class="l-header is-ultimenu__canvas-off"></header>

    <!-- Only one element at the same level here, put it into the container. -->
    <div class="l-page is-ultimenu__canvas-on">
      <div class="l-highlighted"></div>
      <div class="l-featured-top"></div>
      <div class="l-content"></div>
      <div class="l-footer"></div>
    </div>
  </body>
</html>
````

Note the two CSS classes **is-ultimenu__BLAH**, except HTML, is added by
JavaScript based on Steps #4 (**CONFIGURING OFF-CANVAS MENU**), if using the
provided default values. However you might notice a slight FOUC (flash of
unstyled contents). It is because JS hits later after CSS. To avoid such FOUC,
you just have to hard-code those 2 classes directly into your own working theme,
either via `template_preprocess`, or TWIG hacks.

The JavaScript will just ignore or follow later, no problem.

1. HTML: **is-ultimenu**. This has been provided by this module.
2. Header: **is-ultimenu__canvas-off**

   You can also put this class into any region inside Header depending on your
   design needs. Just not as good as when placed in top-level **#header** alike.
   This element, out of canvas by default, can only exist once.
3. Any element below header at the same level: **is-ultimenu__canvas-on**.

   These elements, on canvas by default, will be pushed out of canvas once
   the off-canvas element is in.

Shortly, **is-ultimenu__canvas-off** and **is-ultimenu__canvas-on** must be
siblings, not children of another.

## DOS and DONTS
1. Don't add those 2 later classes to `.is-ultimenu__canvas-off` parent
   elements, otherwise breaking the fixed element positioning. They must all be
   on the same level.
2. Don't add **is-ultimenu__canvas-on** inside **is-ultimenu__canvas-off**.
3. Do try it with core Bartik/ Olivero with default values. Once you know it is
   working, apply it to your own theme.
4. Do sync your hard-coded classes with the provided configurations. Once
   all looks good and working as expected, you can even leave these two options
   empty. They are just to help you speed debugging your off-canvas menu via UI.
5. Do override the relevant `ultimenu.css`, and `ultimenu.offcanvas.css` files.
6. Do keep those classes, unless you know what you are doing.


## Iconized title/description
Since 8.x-2.11, Ultimenu supports iconized menu item description to avoid ugly
titles at administration pages. From now on, title and description are text.
Ultimenu supports a simple iconized text in a pipe delimiter text.
The text must start with either generic **icon-** or fontawesome **fa-** and
separated by a pipe (|) if it has the following text:

* icon-ICON_NAME|Text
* fa-ICON_NAME|Text

**With Title:**

* icon-home|Home
* fa-home|Home
* icon-mail|Contact us

**With Description:**

* icon-home|Some home description
* fa-home|Some home description
* icon-mail|Some contact description

**With EMPTY Description:**
* icon-home
* fa-home
* icon-mail

Basically only if text is prefixed with `fa-` or `icon-` is considered iconized.
Description is optional, title a must.
Adjust the icon styling accordingly, like everything else.

**Repeat!**

Do not always change your Menu item title, else its region will be gone.
Unless using the recommended HASHed region names.

Feel free to change the icon name or description any time, as it doesn't affect
the region key.


## CSS Classes:
The following is a simplified Ultimenu block container.
````
<div class="block block-ultimenu">
  <ul class="ultimenu ultimenu--main">

    <li class="ultimenu__item has-ultimenu">

      <a class="ultimenu__link">Home</a>

      <section class="ultimenu__flyout">
        <div class="ultimenu__region region">Region (blocks + submenu)</div>
      </section>

    </li>

  </ul>
</div>
````

* **HTML.is-ultimenu--hover**: if off-canvas is enabled for mobile only,
  indicating the main menu **has** hoverable states.
* **HTML.is-ultimenu--active**: if off-canvas is enabled for both mobile
  and desktop, indicating the main menu **has no** hoverable states, defined
  via `ultimenu_preprocess_html()`.
  Otherwise this class is only available for mobile only, defined by JS.
* **UL.ultimenu**: the menu UL tag.
* **UL.is-ultihover**: the menu UL tag can have hoverable states
* **UL.is-ulticaret**: the menu links have clickable carets instead of hover.
* **UL.ultimenu > li**: the menu LI tag.
* **UL.ultimenu > LI.has-ultimenu**: contains the flyout, to differ from regular
  list like when using border-radius, etc.
* **SECTION.ultimenu__flyout**: the ultimenu dropdown aka flyout.
* **DIV.ultimenu__region**: the ultimenu region inside the flyout container.
* **A.ultimenu__link**: the menu-item A tag.
* **SPAN.ultimenu__icon**: the menu-item icon tag.
* **SPAN.ultimenu__title**: the menu-item title tag, only output if having icon.
* **A > SMALL**: the menu-item description tag, if enabled.

**Note!**
Both **--hover** and **is-ulticaret** which are normally at desktop are removed
at touch devices to reduce potential CSS complexity, or conflicts.

A very basic layout is provided to display them properly. Skinning is all yours.
To position the flyout may depend on design:

* Use relative `UL` to have a very wide flyout that will stick to menu `UL`.
  This is the default behavior.
* Use relative `LI` to have a smaller flyout that will stick to a menu `LI`:

  `ul.ultimenu > li { position: relative; }`

  If you do this, you may want to add a regular CSS rule `min-width: 600px;`
  (for example) to prevent it from shrinking to its parent `LI` width. Each `LI`
  item has relevant CSS classes, adjust width for each item as needed.
  See `ultimenus.extras.css` for sample about this.

To center the flyout, use negative margin technique:

```
  .ultimenu__flyout {
    left: 50%;
    margin-left: -480px; /* half of width */
    width: 960px;
  }
```

Or with a more modern technique, add prefixes for old browsers:

```
  .ultimenu__flyout {
    left: 50%;
    transform: translateX(-50%); /* half of width */
  }
```

Adjust the margin and width accordingly. The rule: margin is half of width.
Add more specificity to make your overrides win over defaults.


## More ideas for positioning:

- Centered to menu bar
- Always left to menu bar
- Always right to menu bar
- Centered to menu item
- Left to menu item, like Reuters
- Right to menu item

When placing vertical Ultimenu in sidebar, make sure to add position relative
to the sidebar selector, and add proper **z-index**, otherwise it is possible
that the flyout will be dropped behind some content area. Covered by the
optional **ultimenu.extras.css** for now.
