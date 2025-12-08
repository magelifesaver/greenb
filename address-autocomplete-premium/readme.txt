=== Address Autocomplete Anything ===
Contributors: wpsunshine, sccr410
Tags: address, autocomplete, form, woocommerce
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html

Easily integrate Google Address Autocomplete to anything on your WordPress website!

== Description ==

This plugin is unique in that it allows you to add a Google Address Autocomplete to _anything_ on your WordPress website. It is not made to be specific for any one e-commerce, form, LMS, or other WordPress plugin... is compatible with them all!

Address Autocomplete is my favorite feature on any e-commerce site or any time I need to fill out a form on a website. Originally built for our other plugin, [Sunshine Photo Cart](https://wordpress.org/plugins/sunshine-photo-cart/), I realized I could make this available to work for _anything_.

### How it works

By using CSS selectors (don't worry non-tech person, it is easier than you think and a [simple help article and video is available](https://wpsunshine.com/documentation/finding-your-css-selectors/?utm_source=wordpress.org&utm_medium=link&utm_campaign=address-autocomplete-readme)!), you can add Address Autocomplete to Anything! Provide a selector for which input field on your page you want to trigger the address autocomplete when a user types, and then the CSS selectors to target for the address data.

### What you need

You only need to [get a Google Maps API key](https://wpsunshine.com/documentation/google-maps-api-key/?utm_source=wordpress.org&utm_medium=link&utm_campaign=address-autocomplete-readme). Although billing info is required, _most_ sites will never be charged as the free limit is quite high.

[Visit the documentation](https://wpsunshine.com/doccat/address-autocomplete/?utm_source=wordpress.org&utm_medium=link&utm_campaign=address-autocomplete-readme)

### Upgrade to Premium

* Get unlimited instances on your site
* More detailed data fields (latitude, longitude, county, neighborhood, sub localities, etc) to use for population
* Automatically integrate with popular e-commerce and form plugins with one-click set up:
** WooCommerce
** Gravity Forms (Address Field)
** LifterLMS
** Paid Memberships Pro
** ...and more coming _very_ soon!

[Get Premium here](https://wpsunshine.com/plugins/address-autocomplete/?utm_source=wordpress.org&utm_medium=link&utm_campaign=address-autocomplete-readme)

== Installation ==

1. Upload and activate the plugin
2. Get a Google Maps API key
3. Go to Settings > Address Autocomplete and to enter Google Maps API key and form settings
4. Get the CSS selectors for your form and put into the settings

== Changelog ==

= 1.6.2 =
* Fix: WooCommerce - Also set billing address in datastore when "same as billing" is checked

= 1.6.1 =
* Fix: Handle when the address component type had "political" as the first value

= 1.6 =
* New! Fallback value attribute - e.g. {locality:long_name fallback="postal_town:long_name"}

= 1.5.12 =
* Add: Build address2 from premise, floor, subpremise, room values automatically
* Fix: Additional countries that use reverse address format

= 1.5.11 =
* Fix: Handle unique cases like NY burroughs where no locality is returned, use sublocality as fallback

= 1.5.10 =
* Fix: WooCommerce account typo in settings

= 1.5.9 =
* Fix: Handle address_line1 when no street number or route are returned (fallback to place name)

= 1.5.8 =
* Enhancement: More countries added to use street name + number format
* Add: New Results Title option to be shown at top of the address results list

= 1.5.7 =
* Add: New option to set language for returned address results
* Change: More address component fallbacks in case some data is missing from the returned result

= 1.5.6 =
* Fix: Bug where before/after attributes were being ignored in WooCommerce Checkout Block

= 1.5.5 =
* Fix: Work with Pickup locations toggle in WooCommerce Checkout Block
* Fix: "postal_town" fallback when "locality" is not present

= 1.5.4 =
* Fix: Trigger jQuery change event on populated input if jQuery is used on the page just in case
* Fix: Notice to add license key before being able to enable add-on integrations
* Enhancement: Try to match option labels in select fields if no matching value is found

= 1.5.3 =
* Fix: WooCommerce Checkout Block clearing name when address selected

= 1.5.2 =
* Fix: Properly trigger address save for WooCommerce Checkout Block

= 1.5.1 =
* Properly update version number

= 1.5 =
* Set multiple allowed pages for each instance

= 1.4.1 =
* Updates for Paid Memberships Pro new checkout

= 1.4 =
* New! Works with WooCommerce checkout block
* New! Added custom JS events during address replacement
* More console logging to help debug
* NL added to list of countries to do reverse street address format

= 1.3.5 =
* Allow place name to be used in data population
* Fix - Load Google maps with async

= 1.3.4 =
* Fix - Handle address1 when there is no street number

= 1.3.3 =
* Update - Allows more than just addresses, will now accept establishment names
* Fix - Spaces causing issues in before/after attributes

= 1.3.2 =
* Fix - stripslashes on CSS selectors to handle quotes when saving settings

= 1.3.1 =
* Fix - Enqueue Google Maps requires callback function

= 1.3 =
* Update - Better input replacement method and allow for "before" and "after" attributes
* Add - Minified version of frontend.js for even smaller footprint
* Add - Add "subpremise" support

= 1.2.2 =
* Fix - Issues with saving enabled add-ons
* Fix - Settings of automatic integrations now can be customized

= 1.2.1 =
* Fix - PHP warnings when no add-ons selected in admin settings

= 1.2 =
* Add - Integration with Paid Memberships Pro
* Update - LifterLMS countries
* Add - "wps_aa_load_scripts" filter to allow disabling loading of JS files as requested by user for GDPR compliance

= 1.1 =
* Add - Integration with LifterLMS

= 1.0 =
* Change - Complete redo of the way selectors and data fields are chosen for population for even more flexibility
* Update - Adjust add-ons to work with new data structure for selectors

= 0.3 =
* Update documentation links throughout and link to review
* Release to repo for the first time

= 0.2 =
* Fix - More esc_* and sanitization

= 0.1 =
* Initial submission to repo
