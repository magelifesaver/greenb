=== Postie ===
Contributors: WayneAllen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPK99BJ88V4C2
Author URI: http://allens-home.com/
Plugin URI: http://PostiePlugin.com/
Tags: e-mail, email, post-by-email
Requires PHP: 7.0
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 1.9.73
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Postie allows you to create posts via email, including many advanced features not found in WordPress's default Post by Email feature.

== Description ==
Postie offers many advanced features for creating posts by email, including the ability to assign categories by name, included pictures and videos, and automatically strip off signatures. 
Postie supports both IMAP and POP including SSL/TLS.
There is also an extensive set of filters/actions for developers to extend Postie's functionality.
For usage notes, see the [other notes](other_notes) page.

More info at http://PostiePlugin.com/

= Features =
* Supports IMAP or POP3 servers
* SSL and TLS supported
* Control who gets to post via email
* Set defaults for category, status, post format, post type and tags.
* Set title, category, status, post format, post type, date, comment control and tags in email to override defaults.
* Specify post excerpt (including excerpt only images).
* Use plain text or HTML version of email.
* Remove headers and footers from email (useful for posting from a mailing list).
* Optionally send emails on post success/failure.
* Control the types of attachments that are allowed by file name (wildcards allowed) and MIME type.
* Optionally make the first image the featured image.
* Gallery support.
* Control image placement with plain text email.
* Templates for images so they look the way you want.
* Templates for videos.
* Templates for audio files.
* Templates for other attachments.
* Email replies become comments.

= Developers =
* Several filter hooks available for custom processing of emails.
* More developer info at <a href="http://postieplugin.com/extending/">http://postieplugin.com/extending/</a>

== Screenshots ==

1. Postie server options
2. Postie user options
3. Postie message options
4. More message options
5. Even more message options
6. Image options
7. Video and Audio options
8. Attachment options

== Installation ==
* Install Postie either via the WordPress.org plugin directory, or by uploading the files to your server.
* Activate Postie through the Plugins menu in WordPress.
* Configure the plugin by going to the Postie menu that appears in your admin menu.
* Make sure you enter the mailserver information correctly, including the type of connection and the port number. 
* More information can be found at <a href="http://postieplugin.com/">http://postieplugin.com/</a>

== Usage ==

Please visit our site at <a href="http://postieplugin.com/">http://postieplugin.com/</a>

== Frequently Asked Questions ==

Please visit our FAQ page at <a href="http://postieplugin.com/faq/">http://postieplugin.com/faq/</a>
== Upgrade Notice ==

= 1.8.23 =
Postie now respects the blog timezone, this may require you to change the "Postie Time Correction" setting.

= 1.8.0 =
The php-imap library has been replaced with logic based on Flourish fMailbox et al, there are some differences in the structure of the mail header array. This affects the 
postie_filter_email3 and postie_post_before filters.
See http://flourishlib.com/docs/fMailbox

= 1.6.0 =
Remote cron jobs need to update the URL used to kick off a manual email check. The new URL is http://<mysite>/?postie=get-mail
Accessing http://<mysite>/wp-content/plugins/postie/get_mail.php will now receive a 403 error and a message stating what the new URL should be.
The Postie menu is now at the main level rather than a Settings submenu.

= 1.5.14 =
The postie_post filter has be deprecated in favor of postie_post_before.

= 1.5.3 =
Postie can now set the first image in an email to be the "Featured" image. There is a new setting "Use First Image as Featured Image" which is off by default.
Postie now supports Use Transport Layer Security (TLS)

= 1.5.0 =
New filter postie_filter_email. Used to map "from" to any other email. Allows custom user mapping.

= 1.4.41 =
Post format is now supported. You can specify any of the WordPress supported post formats using the Post type syntax.
Post status can now be specified using the status: tag.
Post status setting was renamed to Default Post Status and moved to the Message tab.

= 1.4.10 =
All script, style and body tags are stripped from html emails.

= 1.4.6 =
Attachments are now processed in the order they were attached.

== CHANGELOG ==
= 1.9.73 (2025-09-09) =
* Improved error handling for action/filter calls

= 1.9.72 (2025-09-08) =
* Add error handling to action/filter calls

= 1.9.71 (2025-08-25) =
* address security issue with custom templates

= 1.9.70 (2025-07-14) =
* Additional clarification of draft status with unknown emails
* General improvements

= 1.9.69 (2024-05-08) =
* Additional logging
* Make sure default user is set if no user is found

= 1.9.68 (2023-12-11) =
* Fix email subject and body of confirmation email if there are HTML entities in the blog name.

= 1.9.67 (2023-11-20) =
* Add option to suppress postie div

= 1.9.66 (2023-11-06) =
* Fix deprecation message for PHP 8.2
* Prevent post notification email if post was trashed

= 1.9.65 (2023-01-30) =
* remove calls to uname() as it typically isn't allowed on hosting platforms.

= 1.9.64 (2023-01-24) =
* fix for strange issue with PHP 8.1

= 1.9.63 (2022-12-29) =
* update simple_html_dom
* update requirements (WP5.6/PHP7.0)

= 1.9.62 (2022-09-21) =
* Timezone fix from https://wordpress.org/support/users/glenstewart/
* Add translation support to more strings

= 1.9.61 (2022-06-24) =
* Fix warning when MIME type is not recognized.

= 1.9.60 (2022-04-13) =
* Add translatable strings to email notification

= 1.9.59 (2022-01-02) =
* fix surrounding div with postie-post class name for plain text messages

= 1.9.58 (2021-12-27) =
* deal with possibility of no post formats (reported by @rogerlos)
* add surrounding div with postie-post class name for CSS rules for themes.

= 1.9.57 (2021-09-07) =
* if any attachment doesn't have a file extension use the secondary mime type

= 1.9.56 (2021-08-06) =
* remove Allow HTML In Mail Body from settings as it didn't do anything
* add Allow Duplicate Comments setting to deal with WordPress killing the import
* fix issue where unknown email was leaving tmppost, now creates draft message
* add setting to disable legacy commands

= 1.9.55 (2021-03-09) =
* Add post id to action postie_comment_after
* remove Create Alternate Image Sizes from settings as it didn't do anything
* Add compatibility for WordPress 5.7

= 1.9.54 (2020-10-18) =
* Add compatibility for WordPress 5.5

= 1.9.53 (2020-06-05) =
* Add postie_subject filter

= 1.9.52 (2020-05-19) =
* Fix issue with detecting categories when there are multiple colons in the subject line

= 1.9.51 (2020-05-08) =
* remove ob_end_flush from log_onscreen()

= 1.9.50 (2020-04-21) =
* Add more wp_insert_post failure logging

= 1.9.49 (2020-04-19) =
* Add more wp_insert_post failure logging

= 1.9.48 (2020-04-18) =
* Add more wp_insert_post failure logging

= 1.9.47 (2020-04-16) =
* Add wp_insert_post failure logging

= 1.9.46 (2020-04-11) =
* escape IMAP password
* fix logging in get_parent_postid
* ensure any modification by shortcode are retained

= 1.9.45 (2020-03-29) =
* Fix email notification bug

= 1.9.44 (2020-03-23) =
* refactoring to separate email fetch from email processing
* add postie_register_shortcode_pre action for registering Postie shortcodes

= 1.9.43 (2020-02-18) =
* Begin migration of shortcode support into Postie main

= 1.9.42 (2020-02-18) =
* Fix: signature stripping in html emails was failing sometimes

= 1.9.41 (2020-02-01) =
* Fix: different regex approach for html vs plain
* Only process 1 email at a time
