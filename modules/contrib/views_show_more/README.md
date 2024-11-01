# CONTENTS OF THIS FILE

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * FAQ
 * Maintainers


## INTRODUCTION

The Views Show More is a unique pager plugins for view.
It supports different item count in 1st page and others pages.
Like, you have set 6 items per page. But want to set 12 items
initially in 1st page. This module cans it.

 * For a full description of the module, visit the project page:
   <https://drupal.org/project/views_show_more>

 * To submit bug reports and feature suggestions, or to track changes:
   <https://drupal.org/project/issues/views_show_more>


## FEATURES

 * Works both views ajax and no-ajax mode (developed mainly for the ajax mode).
 * Option for result display method. Can choose Append or Replace method.
 * Option to override 1st page or initial item count than others page.
 * Basic and advance animation system for result impression for ajax mode.
 * Ability to define custom animation timing.
 * Advance settings for content area and pager area selector. If default html
  markup override using the theme templates.


## REQUIREMENTS

Drupal core views


## INSTALLATION

Install as you would normally install a contributed Drupal module. Visit:
<https://www.drupal.org/node/1897420> for further information.


## CONFIGURATION

Go to Administration » Structure » Views:

 * Create a new view or edit an existing one.
 * Add or select any display type like page, block etc.
 * In pager settings section click pager chooser link to change pager type.
 * Select Show More Pager and click apply.
 * In settings page enter the initial item count, per page item
  count, pager text etc.
 * Save pager settings.
 * Enable ajax mode and save the view.
 * Now check the view in front end, and you will get your desire output.
 * Show More Pager system will not work in views live preview.


## FAQ

1. What is result display method?

    Result display method used to control result impression system.
    Append result display method append the new content after the existing
    content on the page in ajax mode and in no-ajax mode replace the content
    by page refresh.
    Replace result display method replace the content with new content both
    in ajax and no-ajax mode. In no-ajax mode it refreshes the page.

2. What is show more pager link text and empty text?

    This text used for pager next page link (instead of 1, 2, 3, next >>).
    Empty text used for pager when no result available to show. Like if you
    last of the result set then this empty text will show instead of the pager
    links (if empty text set).

3. What is item per page?

    It same as item count per page of normal pager.

4. What is initial item?

    It is the option to override the 1st page item count. If it's value 0 then
    it use item per page as initial item, but it set then it show the override
    result count.

5. How to use animation?

    In animation section click animation to expend.
    Select any animation type form the 4 options.
     * None - No animation.
     * FadeIn - Result fadein when show more pager link clicked.
     * Scroll to new content - When show more pager link clicked, the viewport
       scroll to the new result.
     * Scroll to new content and FadeIn - When show more pager link clicked, 1st
       viewport scroll to the new result area then the result will fadein.

    Select animation speed, form available 3 options.
     * Slow - It is equivalent to 600ms.
     * Fast - It is equivalent to 200ms.
     * Custom - When it selected then a new field will shown named Animation
      speed in ms(millisecond). Here you need to put your custom animation
      duration like 400, 800. On this field only integer value is possible.

6. How to use advanced option to control the content and pager selector?

    If your views default markup need to change then you can specify the content
    area and pager area selector.
    For content use the content jquery selector. Default is .view-content
    For pager use the pager jquery selector. Default is .pager-show-more


## MAINTAINERS

* Andrei Ivnitskii - <https://www.drupal.org/u/ivnish>
