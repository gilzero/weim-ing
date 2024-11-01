CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* How it works
* Support requests
* Maintainers


INTRODUCTION
------------

Social Auth Spotify is a Spotify authentication integration for
Drupal. It is based on the Social Auth and Social API projects

It adds to the site:

* A new url: `/user/login/spotify`.

* A settings form at `/admin/config/social-api/social-auth/spotify`.

* A Spotify logo in the Social Auth Login block.


REQUIREMENTS
------------

This module requires the following modules:

* [Social Auth](https://drupal.org/project/social_auth)
* [Social API](https://drupal.org/project/social_api)


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See
[Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules)
for more details.


CONFIGURATION
-------------

In Drupal:

1. Log in as an admin.

2. Navigate to Configuration » User authentication » Spotify and copy
   the Authorized redirect URL field value (the URL should end in
   `/user/login/spotify/callback`).

In [Spotify for Developers](https://developer.spotify.com/):

3. Log in to your Spotify account.

4. Navigate to [Dashboard](https://developer.spotify.com/dashboard).

5. Click [Create app](https://developer.spotify.com/dashboard/create).

6. Fill out the Create app form opting to use the Web API, and pasting the
   redirect URL from Step 2 into the Redirect URIs field, and click Save

7. From the dashboard click on your new app to view your Client ID and Client
   Secret to somewhere safe

In Drupal:

8. Return to Configuration » User authentication » Spotify

16. Enter the Spotify Client ID in the Client ID field.

17. Enter the Spotify Client Secret in the Client Secret field.

18. Enter the API version (usually `1`) in the API version field.

19. Click Save configuration.

20. Navigate to Structure » Block Layout and place a Social Auth login block
    somewhere on the site (if not already placed).

That's it! Test the connection by logging in with your own account. For further
testing navigate to Roles > Test Users to create and add other testers.

When ready log in to Spotify for Developers, navigate to your app, click on
Extension Requests to request for your app to go live so any users can log in.


HOW IT WORKS
------------

The user can click on the Spotify logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points
to `/user/login/spotify`, so theming and customizing the button or link
is very flexible.

After Spotify has returned the user to your site, the module compares the user
ID or email address provided by Spotify. If the user has previously registered
using Spotify or your site already has an account with the same email address,
the user is logged in. If not, a new user account is created. Also, a Spotify
account can be associated with an authenticated user.


SUPPORT REQUESTS
----------------

* Before posting a support request, carefully read the installation
  instructions provided in module documentation page.

* Before posting a support request, check the Recent Log entries at
  admin/reports/dblog

* Once you have done this, you can post a support request at module issue
  queue: [https://www.drupal.org/project/issues/social_auth_spotify](https://www.drupal.org/project/issues/social_auth_spotify)

* When posting a support request, please inform if you were able to see any
  errors in the Recent Log entries.


MAINTAINERS
-----------

Current maintainers:

* [Owen Bush (owenbush)](https://www.drupal.org/u/wells)

Development sponsored by:

* [Lullabot](https://www.drupal.org/lullabot)
