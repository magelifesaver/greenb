=== SWIS Performance ===
Contributors: nosilver4u
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.3.0
License: GPLv3

It makes your site faster, and bakes you a cake too! Alright, maybe no cakes...

== Description ==

SWIS is a collection of tools for improving the speed of your site. It includes the following:
* Page Caching to reduce the overhead of PHP and database queries. AKA: super speed boost.
* Defer/Delay JS to prevent render-blocking scripts with flexible rules engine for per-page and per-script optimization.
* Load CSS asynchronously to prevent render-blocking CSS.
* Inline critical CSS to prevent a Flash of Unstyled Content (FOUC) when using async CSS.
* Minify JS/CSS to trim out extra white-space.
* Compress all assets and set proper expiration headers for awesome browser caching.
* Deliver all static resources from a CDN. CDN sold separately, perhaps you'd like https://ewww.io/easy/
* Disable unused JS/CSS resources. Fully customizable, load/unload files site-wide, or on specific pages.
* DNS pre-fetch and pre-connect hints so browsers can load third-party assets quicker.
* Optimize Google Fonts.

== Changelog ==

= 2.3.0 =
*Release Date = November 7, 2024*

* added: store errors from Critical CSS generation for future reference
* changed: Critical CSS generation included with SWIS license (beta)

= 2.2.2 =
*Release Date - October 24, 2024*

* fixed: post-enqueue JS/CSS parser uses incorrect handles
* fixed: some JS variable definitions not excluded from JS defer

= 2.2.1 =
*Release Date - October 10, 2024*

* fixed: CDATA exclusion for inline JS defer was too aggressive
* fixed: is_file() wrapper method triggers PHP warning on empty path
* fixed: caching system not fully removed after disabling page caching
* fixed: WP_CACHE constant sometimes not detected after enabling page caching

= 2.2.0 =
*Release Date - August 15, 2024*

* added: self-host Google Fonts for improved privacy and completely eliminate extra DNS/HTTP requests
* added: Google Fonts optimized in stylesheets where fonts are mixed with additional CSS
* added: non-enqueued Google Fonts can be managed and disabled with Slim

= 2.1.4 =
*Release Date - July 31, 2024*

* changed: improved performance of is_file() wrapper method(s)
* fixed: errors when clearing server caches due to namespace resolution

= 2.1.3 =
*Release Date - July 25, 2024*

* changed: ensure SWIS front-end options are autoloaded in WP 6.6
* fixed: Help links broken in Firefox's Strict mode
* fixed: undefined variable affecting AMP output

= 2.1.2 =
*Release Date - April 11, 2024*

* added: compatibility with Slider Revolution 7 rendering engine
* changed: home page cache is automatically purged when updating a post/page
* improved: detection for login page in Critical CSS function

= 2.1.1 =
*Release Date - December 20, 2023*

* fixed: disabling cache clear on plugin upgrade (via override) does not pre-empt all cache clearing
* fixed: compatibility with X/Pro themes and Cornerstone builder
* fixed: PHP notices when cache settings file is outdated

= 2.1.0 =
*Release Date - August 31, 2023*

* added: dns-prefetch and preconnect hints for Bunny Fonts
* added: WPML support for Page Cache and Critical CSS
* added: Optimize CSS preloads WPBakery, Visual Composer, and Zion Builder CSS
* added: override for CDN domain via SWIS_CDN_DOMAIN constant
* changed: updated JS/CSS Minify library
* fixed: cache engine throws preg_grep() error on multi-site sub-domain installs in rare cases
* fixed: on multisite, database table upgrades may not run prior to cache preload and other background processes
* fixed: some front-end functions produce AMP validation errors
* fixed: Optimize JS breaks some inline scrips when they contain comments
* fixed: PHP 8.1/8.2 deprecation notices
* security: randomize filename of debug log

= 2.0.4 =
*Release Date - July 19, 2023*

* added: dns-prefetch and preconnect hints for CSS resources
* changed: dashicons not disabled by Slim if WP admin bar is visible
* fixed: PHP 8.1 deprecation notices from usage of add_submenu_page and add_query_arg
* fixed: PHP notice when getting cache size on disk
* fixed: preconnect hints (incorrectly) skipped if dns-prefetch hint exists
* fixed: guest asset check fails in some cases
* fixed: Slim displays duplicate dependency information
* fixed: Slim missing id attributes for form elements

= 2.0.3 =
*Release Date - December 21, 2022*

* changed: improved Brizy Builder compatibility
* changed: exclude .build.* files from minification
* fixed: prevent JS/CSS Minify from breaking on assets generated via admin-ajax.php
* fixed: front-end Critical CSS generation broken with JS error

= 2.0.2 =
*Release Date - November 16, 2022*

* fixed: AffiliateWP JS broken when inline scripts are deferred
* fixed: CDN rewriting leaks into some image URLs when Easy IO is active
* fixed: CDN rewriting leaks into post editor
* fixed: Thrive quiz builder broken with SWIS minify/defer
* fixed: PHP warnings generated during theme update

= 2.0.1 =
*Release Date - November 8, 2022*

* changed: use defer safe mode for jQuery when inline scripts are not deferred
* fixed: inline JS for Real Cookie Banner breaks when deferred
* fixed: inline data script elements broken by JS defer
* fixed: inline script elements with reserved characters malformed
* fixed: slim.js throws errors on wp-admin
* fixed: Slim front-end panel breaks if jQuery is loaded late using non-standard hooks

= 2.0.0 =
*Release Date - October 27, 2022*

* added: customize JS/CSS defer per-page via the Slim front-end panel (Manage JS/CSS)
* added: delay JS per-script and per-page via Slim front-end panel
* added: defer or delay inline scripts
* added: disable, defer, and delay scripts that do not use the standard enqueue system
* added: Test Mode to limit JS/CSS optimizations and Slim rules to logged-in admins
* added: JS errors indicated on Slim panel
* changed: if JS files are deferred, inline scripts will also be deferred by default
* changed: hide assets from Slim that are not used for non-logged in visitors

See separate changelog.txt for full history.
