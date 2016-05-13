# MediaWiki Shibboleth Authentication Plug-in.

This plug-in integrates [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) with the [Shibboleth](https://shibboleth.net/) SSO platform.

## Installation

1. Create a Shibboleth directory in `$MW_ROOT/extensions`.
2. Copy the PHP files `ShibAuthPlugin.php` and `ShibAuthSetup.php` to this directory.
3. (Optionally) Copy the lazy-session file `Login.php` to `$MW_ROOT/`.
4. Use the example `LocalSettings.php` file to configure the plug-in.
5. Use the example `.htaccess` file to enable Shibboleth on your host.
6. You're done!

## Authors

* Portions Copyright 2006, 2007 Regents of the University of California.
* Portions Copyright 2007, 2008 Steven Langenaken
* Portions Copyright 2016 Joseph Chagnon
