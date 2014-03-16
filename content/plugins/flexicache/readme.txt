=== FlexiCache ===

Contributors: simon.holliday
Tags: cache,caching,availability,performance,uptime,speed,memcache
Tested up to: 3.5
Stable tag: 1.2.4.4
Requires at least: 2.6
Donate link: http://simonholliday.com/

FlexiCache is a fast, full-featured and flexible caching system which will improve the performance and availability of any WordPress site

== Description ==

FlexiCache is a fast, full-featured and flexible caching system which will improve the performance and availability of any WordPress site.

It is highly configurable to allow unlimited caching rules - expiring different pages at different times, not caching some pages at all, caching multiple versions of the same page depending on browser, language, and so on.

FlexiCache will also work straight out of the box without any need for complex configuration.

= Features =

* Extensively configurable using user-defined conditions
* Handling of high-traffic periods where multiple clients may simultaneously request uncached or expired items
* Standalone mode which bypasses WordPress and third-party theme and plugin code to serve cached pages faster
* Choice of storage engines: Filesystem, Memcache and SQLite
* Pre-caching functionality to cache new versions of pages before the existing ones expire
* SQLite indexing of Filesystem and Memcache stores to speed up removing specific items from the cache (automatically enabled if SQLite is available)
* Sends appropriate HTTP cache headers to the requesting client
* Serves files in compressed formats accepted by the requesting client
* Compatible with WordPress MU

== Upgrade Notice ==

= 1.2.4.4 =

If you upgrade the plugin using WordPress's built-in upgrade process, the entire plugin directory will be replaced, including your config and cache data.

To preserve these, take a copy of the "plugins/FlexiCache/_data" directory before upgrading, and then copy it back after the upgrade is complete.

If you upgrade directly via SVN, this should not be required, since only the files under version control will be updated.

= 1.2.4.3 =

If you upgrade the plugin using WordPress's built-in upgrade process, the entire plugin directory will be replaced, including your config and cache data.

To preserve these, take a copy of the "plugins/FlexiCache/_data" directory before upgrading, and then copy it back after the upgrade is complete.

If you upgrade directly via SVN, this should not be required, since only the files under version control will be updated.

= 1.2.1.4 =

This version adds better handling for when multiple requests are received for uncached and expired items, and the option to send HTTP 503 "Service Unavailable" responses to user agents which request an uncached page which is currently in the process of being built.

It should be safe to upgrade without issue, however as this version adds some new config items it is recommended that you click "Save Changes" in the "Main Options" page of the admin area once upgraded, which will add the new config items to your config file and remove the need for the plugin to check the defaults file.

== Changelog ==

= 1.2.4.4 =

* Testing in WordPress 3.5

= 1.2.4.3 =

* Testing in WordPress 3.4

= 1.2.4.2 =

* Tested in WordPress 3.3.1
* Documentation and admin CSS updated
* No changes to functionality

= 1.2.4.1 =

* Testing in WordPress 3.2.1
* No changes to functionality

= 1.2.4.0 =

* Use the single "transition_post_status" action in place of "publish_post", "trash_post" and "delete_post" used previously, to enable automatic removal of a cached item on any post status transition
* Disable automatic update of .htaccess file for installations running Multisite
* Testing in WordPress 3.1.4

= 1.2.3.1 =

* Tested in WordPress 3.1.3
* No changes to functionality

= 1.2.3.0 =

* Allow addition of random element to expiry headers sent to client, to prevent multiple clients attempting to fetch fresh copies of items simultaneously
* Testing in WordPress 3.1
* Text/comments changes and corrections

= 1.2.2.0 =

* Address a bug in versions of Memcached from 1.4.0 to 1.4.3 - see http://code.google.com/p/memcached/wiki/ReleaseNotes144
* Add the number of cached responses to WordPress's "Right Now" Dashboard box if indexing is enabled

= 1.2.1.9 =

* Allow preview of standalone modification to .htaccess file before committing the change
* Consistent internal method for generating plugin admin URL

= 1.2.1.6 =

* Disable caching for clients with a passworded page cookie set
* Show serve mode (plugin/standalone) in custom HTTP header

= 1.2.1.4 =

* Improved handling for multiple requests to uncached and expired items
* Option to send 503 "Service Unavailable" responses to user agents which request an uncached page which is currently in the process of being built
* Removal of "no-store" directive in custom control headers
* Addition of "must-revalidate" and "no-pre-cache" directives in custom control headers
* Don't enable pre-caching by default
* Improved documentation

= 1.1.0.2 =

* Additional security restriction to disable storing of pages for comment authors, as WordPress may pre-fill forms with their details in.

= 1.1.0.1 =

* Fixed some broken HTML in the admin area.

= 1.1 =

* First publicly available version

== Installation ==

Please note that FlexiCache requires at least PHP 5.

For cache indexing, you should enable the SQLite extension (http://www.php.net/manual/en/sqlite.installation.php), but this is not a requirement for the plugin to work.

1. Copy the flexicache plugin directory into your WordPress "plugins" directory (the new directory should be called "wp-content/plugins/flexicache")
2. If you intend to run the plugin in Standalone mode, check that [mod_rewrite](http://httpd.apache.org/docs/2.1/mod/mod_rewrite.html) is enabled in Apache.
3. If you intend to use the Memcache storage engine, you will need a [Memcached server](http://memcached.org/) and [PHP's Memcache extension](http://php.net/manual/en/book.memcache.php).
4. In your WordPress admin area, visit the "Plugins" page and activate FlexiCache.  FlexiCache will not start caching until it is also enabled in its own config.
5. Ensure that your web server has permission to write to the "_data" directory inside the plugin directory.
6. Click on the "Configuration & Status" link (or go to Settings | FlexiCache) and read the Documentation section.
7. Configure and enable FlexiCache.

== Upgrading FlexiCache ==

FlexiCache stores config and cache data within the plugin directory (see plugin documentation in versions 1.2.4.2 onwards for the reason why).

If you upgrade the plugin using WordPress's built-in upgrade process, the entire plugin directory will be replaced, including your config and cache data.

To preserve these, take a copy of the "plugins/FlexiCache/_data" directory before upgrading, and then copy it back after the upgrade is complete.

If you upgrade directly via SVN, this should not be required, since only the files under version control will be updated.

== Deactivating FlexiCache ==

FlexiCache can be disabled via the admin interface as with any plugin, however if you are running in Standalone mode, you should remove the .htaccess modifications before deactivating the plugin.

For a standard WordPress install, this modification can be done via the admin interface, however it is recommended that you make this change manually, especially if using WordPress MU, where multiple sites may share the same .htaccess file.

Please see the documentation supplied with the plugin (accessible via WordPress's admin interface) for more information.

== Frequently Asked Questions ==

= Where do babies come from? =

That is beyond the scope of a caching plugin.
