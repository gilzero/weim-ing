Module Builder
==============

Welcome to Module Builder!

Module Builder is a system that simplifies the process of creating code, by
creating files complete with scaffold code that can be filled in.

For example, for generating a custom module, simply fill out the form, select
the hooks you want and the script will automatically generate a skeleton module
file for you, along with PHPDoc comments and function definitions. This saves
you the trouble of looking at api.drupal.org 50 times a day to remember what
arguments and what order different hooks use. Score one for laziness! ;)

What Module Builder can create
------------------------------

Module builder can generate the following for a module:
- code files, containing hook implementations
- info file (.info.yml on Drupal 8)
- plugin classes
- PHPUnit test case classes
- content and config entity types
- services
- plugin types
- README file

Furthermore, complex subcomponents can generate multiple code elements:
- an admin settings form adds form builder functions and an admin permission
- router paths add menu/router items
- permission names add the scaffold for the permission definition (on D7 and,
  earlier, hook_permission(), on D8 a permissions.yml file)

Installing Module Builder
-------------------------

Install Module Builder with Composer, the same as any other Drupal module. This
will take care of installing Drupal Code Builder and other dependencies.

WARNING: Module Builder is a developer tool. It should NEVER be installed on a
production site, as it has the capability to write to the site's codebase.

Running code analysis
---------------------

Module Builder uses code analysis to build a list of all the different hooks,
plugins types, services, and so on, that are defined by Drupal core, and contrib
and custom modules.

You need to run this when you first install Module Builder, and you should run
this again each time you enable new modules, update modules, or add code to your
custom modules.

1. Go to Administration › Configuration › Development › Module Builder › Analyse
   code.
2. Click the 'Update code analysis' button.

Using Module Builder
--------------------

1. Go to Administration › Configuration › Development › Module Builder.
   (Note: you will require 'create modules' privileges to see this link.)
2. Enter a module name, description, and so on. Save the form.
3. Select the components you want to add from the different tabs.
4. Go to the "Generate" tab and watch your module's code generated
   before your eyes! ;)
5. Use the 'Write files' button to write the generated code to your file system.
   Alternatively, copy and paste the code into using the shown filenames in a
   <your_module> directory under one of the modules directories.
6. Start customizing it to your needs; most of the tedious work is
   already done for you! ;)
7. Your module is saved as an entity. You can return to it, change the
   components and generate the code again.

Todo/wishlist
-------------

See the issue queue for this module on drupal.org, and the issue queue for
Drupal Code Builder on github:

- https://www.drupal.org/project/issues/module_builder
- https://github.com/drupal-code-builder/drupal-code-builder

CONTRIBUTORS
------------
* Owen Barton (grugnog2), Chad Phillips (hunmonk), and Chris Johnson
  (chrisxj) for initial brainstorming stuff @ OSCMS in Vancouver
* Jeff Robbins for the nice mockup to work from and some great suggestions
* Karthik/Zen/|gatsby| for helping debug some hairy Forms API issues
* Steven Wittens and David Carrington for their nice JS checkbox magic
* jsloan for the excellent "automatically generate module file" feature
* Folks who have submitted bug reports and given encouragement, thank you
  so much! :)

ATTRIBUTION
-----------

Project icon from https://iconduck.com/emojis/18090/building-construction.
