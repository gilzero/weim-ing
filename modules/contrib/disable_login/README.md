INTRODUCTION
============

Disable Login Page is a simple module that prevents access to the default
Drupal Login Page to anonymous users without the use of a secret key. This
is useful for sites that do not have any public user login requirements
like in a corporate website or a personal blog.

The login page is protected with a secret key name value pair which can be
set by the admin. When the default login page is accessed without the secret
key-value pair, you get an access denied error.

The module also allows the secret key to be programmatically modified by any
custom code by exposing an alter hook on the key. If you want to use a custom
logic for generating the key you can implement the alter hook in your module.

Users who want to login to the site can bookmark the login page including
the secret key


EXAMPLES
--------

Admin user can set both the name of the QueryString and the value which
will allow access to the login page.

Eg:

http://example.com/user/login

will give access denied.

http://example.com/user/login?key=secret

will allow the user to access the login page.


REQUIREMENTS
------------

The module works on Drupal 8 and 9.


INSTALLATION
------------

To install this module, `composer require` it, or  place it in your modules
folder and enable it on the modules page.


CONFIGURATION
-------------

All settings for this module are on the Disable Login configuration page,
under the Configuration section, in the Security sub menu. You can visit the
configuration page directly at admin/config/security/disable-login.

In addition, you can also completely prevent access to /user/login
when accessed without any querystrings, by configuring this at
the webserver level.

For apache you can add the following before the section on the rediret
to index.php.

```
  # Block access to /user/login when there are no querystrings
  RewriteCond %{QUERY_STRING} ^$
  RewriteCond %{REQUEST_URI} ^/user/login
  RewriteRule ^.* - [F,L]
```
Other webservers can be configured similarly.


IF YOU GET LOCKED OUT
---------------------

If you are locked out of the system because you forgot the key-value pair

- If you have access to shell, run the following

```
drush -y config-set disable_login.settings disable_login 0
```

- If you don't have access to shell but have access to the file system via ftp

Edit disable_login/src/Access/DisableLoginAccessCheck.php file
and find ```hasValidSecretToken``` function.
Add ```return TRUE;``` as the first line in the function.
