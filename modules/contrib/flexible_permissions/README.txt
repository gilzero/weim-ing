The Flexible permissions module allows you to gather, calculate and cache
permissions from a myriad of sources. By virtue of a centralized permission
checker service, it enables you to turn your website's access control layer
into a Policy Based Access Control.

For instance, you could have no editors be allowed to edit any content by
default, but have a permission calculator that adds these permissions during
office hours. The cache (built using VariationCache) would be smart enough to
recognize this and serve the user a more permissive set of permissions during
office hours, allowing them to edit content.

Right now, the module needs to be implemented by any access defining module
such as Group, Domain, Commerce Stores, etc. The Group module already relies
on this module as of version 2.0.0.

Scope based permissions

You can define permissions for each scope. Scopes are a way to determine where
the permissions should be checked to give access. Your regular Drupal
permissions could be regarded as being in the "default" scope, but each
implementation is free to add their own scopes.

As an example: The Group module does not check for regular Drupal permissions.
Instead, it has its own permission layer where access is checked versus group
types when you are or aren't a member (group_outsider and group_insider scopes)
and against individual memberships (group_individual).

The Domain module would be able to define permissions specifically for certain
domains and Commerce would be able to do the same on a per-store basis.
