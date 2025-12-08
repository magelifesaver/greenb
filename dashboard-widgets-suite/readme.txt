=== Dashboard Widgets Suite ===

Plugin Name: Dashboard Widgets Suite
Plugin URI: https://perishablepress.com/dashboard-widgets-suite/
Description: Provides a suite of sweet widgets for your WP Dashboard.
Tags: dashboard, widget, debug, log, notes
Author: Jeff Starr
Contributors: specialk
Author URI: https://plugin-planet.com/
Donate link: https://monzillamedia.com/donate.html
Requires at least: 4.7
Tested up to: 6.9
Stable tag: 3.4.9
Version:    3.4.9
Requires PHP: 5.6.20
Text Domain: dashboard-widgets-suite
Domain Path: /languages
License: GPL v2 or later

Adds 9 awesome widgets to your WP Dashboard. Includes User Notes, Social Buttons, System Info, Debug/Error Logs, and more!



== Description ==

1 Plugin. 9 Widgets. Awesome Dashboard.

_Add new widgets to your WordPress Dashboard. Streamline your workflow and optimize productivity._

**Widgets**

Dashboard Widgets Suite provides awesome widgets that you can add to your Dashboard with a click:

* __Control Panel__ - Control widgets directly from the Dashboard
* __User Notes__    - Add, edit, delete notes for any user role
* __Feed Box__      - Display and customize any RSS Feed
* __Social Box__    - Display social links from Twitter, Facebook, etc.
* __List Box__      - Display custom lists created via the Menu screen
* __Widget Box__    - Display any theme or WP widget (e.g., Search, Text)
* __System Info__   - Display basic or advanced system information
* __Debug Log__     - Display, customize, reset your WP debug log
* __PHP Error Log__ - Display, customize, reset your server error log

Each widget includes its own set of options for customizing display :)

The notes widget is super awesome, designed for serious note takers. You can choose from 3 note formats: Text, HTML, or Code. Check out [Installation](https://wordpress.org/plugins/dashboard-widgets-suite/#installation) for more about the User Notes Widget.

_[Suggest a widget!](https://plugin-planet.com/support/#contact)_



**Features**

Dashboard Widgets Suite provides a slick set of useful Dashboard widgets with some great features:

* Includes 9 awesome Dashboard widgets
* Easy to set up and customize options
* Regularly updated and "future proof"
* Secure, clean, error-free code
* Built with the WordPress API
* Lightweight, fast and flexible
* Focused on performance, loads only enabled widgets
* Enable/disable widgets via Dashboard or plugin settings
* Option to restore 1-column display for the Dashboard
* Shortcodes to display widgets on the frontend
* Many options for customizing widget display
* Works perfectly with or without Gutenberg

[Check out the Screenshots](https://wordpress.org/plugins/dashboard-widgets-suite/screenshots/) for more details!

_[Suggest a feature!](https://plugin-planet.com/support/#contact)_



**Privacy**

This plugin does not collect or store any user data. It does not set any cookies, and it does not connect to any third-party locations. Thus, this plugin does not affect user privacy in any way.

Dashboard Widgets Suite is developed and maintained by [Jeff Starr](https://x.com/perishable), 15-year [WordPress developer](https://plugin-planet.com/) and [book author](https://books.perishablepress.com/).



**Support development**

I develop and maintain this free plugin with love for the WordPress community. To show support, you can [make a donation](https://monzillamedia.com/donate.html) or purchase one of my books: 

* [The Tao of WordPress](https://wp-tao.com/)
* [Digging into WordPress](https://digwp.com/)
* [.htaccess made easy](https://htaccessbook.com/)
* [WordPress Themes In Depth](https://wp-tao.com/wordpress-themes-book/)
* [Wizard's SQL Recipes for WordPress](https://books.perishablepress.com/downloads/wizards-collection-sql-recipes-wordpress/)

And/or purchase one of my premium WordPress plugins:

* [BBQ Pro](https://plugin-planet.com/bbq-pro/) - Blazing fast WordPress firewall
* [Blackhole Pro](https://plugin-planet.com/blackhole-pro/) - Automatically block bad bots
* [Banhammer Pro](https://plugin-planet.com/banhammer-pro/) - Monitor traffic and ban the bad guys
* [GA Google Analytics Pro](https://plugin-planet.com/ga-google-analytics-pro/) - Connect WordPress to Google Analytics
* [Head Meta Pro](https://plugin-planet.com/head-meta-pro/) - Ultimate Meta Tags for WordPress
* [Simple Ajax Chat Pro](https://plugin-planet.com/simple-ajax-chat-pro/) - Unlimited chat rooms
* [USP Pro](https://plugin-planet.com/usp-pro/) - Unlimited front-end forms

Links, tweets and likes also appreciated. Thank you! :)



== Screenshots ==

1.  DWS Dashboard: All widgets enabled
2.  DWS Settings:  General Settings
3.  DWS Settings:  User Notes
4.  DWS Settings:  Feed Box
5.  DWS Settings:  Social Box
6.  DWS Settings:  List Box
7.  DWS Settings:  Widget Box
8.  DWS Settings:  System Info
9.  DWS Settings:  Debug Log
10. DWS Settings:  Error Log



== Installation ==

**Installing Dashboard Widgets Suite**

1. Upload the plugin to your blog and activate
2. Visit the settings and enable desired widgets
3. Visit the WP Dashboard to use your new widgets

Tip: you can enable/disable widgets via the plugin settings or via the Control Panel widget on the Dashboard.

_[More info on installing WP plugins](https://wordpress.org/support/article/managing-plugins/#installing-plugins)_


**Like the plugin?**

If you like Dashboard Widgets Suite, please take a moment to [give a 5-star rating](https://wordpress.org/support/plugin/dashboard-widgets-suite/reviews/?rate=5#new-post). It helps to keep development and support going strong. Thank you!


**User Notes Widget**

As of DWS version 1.9, you can choose a format for each user note:

* Text (default)
* HTML
* Code

How to choose a note format:

1. Click the "Add Note" link
2. In the "Format" select menu, choose your format
3. Write your note how you want, click the "Add Note" button and done!

To __Edit__ or __Delete__ any note, double-click on the note content (to display the "Edit" and "Delete" buttons).


**Debug and Error Logs**

Note that the Debug and Error Log widgets may require a bit of configuration, depending on your WP setup. Here is a quick guide:

__Debug Log__

To enable the WP Debug Log for the Debug Log widget, make sure that debug mode is enabled in your site's `wp-config.php` file. Here is one possible way to enable, by adding the following code to your wp-config file, just before the line that says, "That's all, stop editing!":

	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);
	define('WP_DEBUG_DISPLAY', false);

Once added, this will tell WP to log all errors, warnings, and notices to a file named `debug.log`, which is located in the `/wp-content/` directory. Note that if the file does not exist, you can create it manually and give it suitable permissions. Ask your web host if unsure.

__Error Log__

To enable the Error Log for the Error Log widget, follow the same steps as for "Debug Log", but use this code instead:

	define('WP_DEBUG', true); 
	ini_set('display_errors', 'Off');
	ini_set('error_reporting', E_ALL);

And also make sure to set the correct file path under the plugin's "Error Log" tab, in the setting "Log Location".

__Debug Log and Error Log__

To enable both Debug Log and Error Log, follow the same steps as above, but use this code instead:

	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);
	define('WP_DEBUG_DISPLAY', false);
	ini_set('display_errors', 'Off');
	ini_set('error_reporting', E_ALL);

For more information, visit [Debugging in WordPress](https://wordpress.org/support/article/debugging-in-wordpress/).


**Uninstalling**

This plugin cleans up after itself. All plugin settings will be removed from the WordPress database when the plugin is uninstalled via the Plugins screen.

__Note:__ User Notes are not deleted, so if you want to delete them, do so via the WP Dashboard before uninstalling the plugin.


**Restore Default Options**

To restore default plugin options, either uninstall/reinstall the plugin or visit the General Settings &gt; Restore default plugin options.


**Shortcodes**

DWS provides several shortcodes for displaying widgets on the frontend of your site. Here is a summary:

	[dws_feed_box]   => Feed Box
	[dws_social_box] => Social Box
	[dws_user_notes] => User Notes
	
You can add these to any WP Post or Page to display the widget on the frontend. The same widget settings apply to both frontend and backend display.



**Customizing**

Dashboard Widgets Suite provides plenty of settings to customize your widgets. For advanced customization, developers can tap into the power of WordPress Action and Filter Hooks. Here is a complete list of the hooks provided by Dashboard Widgets Suite:


	Action Hooks
	
	dashboard_widgets_suite
	dashboard_widgets_suite_control_panel
	
	dashboard_widgets_suite_feed_box
	dashboard_widgets_suite_feed_box_frontend
	
	dashboard_widgets_suite_list_box
	
	dashboard_widgets_suite_log_debug
	dashboard_widgets_suite_log_error
	
	dashboard_widgets_suite_notes_user
	dashboard_widgets_suite_notes_user_submit
	dashboard_widgets_suite_notes_user_frontend
	
	dashboard_widgets_suite_social_box
	dashboard_widgets_suite_social_box_frontend
	
	dashboard_widgets_suite_system_info
	
	dashboard_widgets_suite_widget_box


	Filter Hooks
	
	dashboard_widgets_suite_options_general
	dashboard_widgets_suite_get_options_general
	
	dashboard_widgets_suite_options_feed_box
	dashboard_widgets_suite_get_options_feed_box
	dashboard_widgets_suite_feed_box_data
	dashboard_widgets_suite_feed_box_output
	dashboard_widgets_suite_feed_box_suffix
	dashboard_widgets_suite_feed_box_frontend_data
	
	dashboard_widgets_suite_options_list_box
	dashboard_widgets_suite_get_options_list_box
	dashboard_widgets_suite_list_box_menu_name
	
	dashboard_widgets_suite_options_log_debug
	dashboard_widgets_suite_get_options_log_debug
	dashboard_widgets_suite_log_debug_clear
	dashboard_widgets_suite_log_debug_errors
	dashboard_widgets_suite_log_debug_level
	dashboard_widgets_suite_log_debug_path
	
	dashboard_widgets_suite_options_log_error
	dashboard_widgets_suite_get_options_log_error
	dashboard_widgets_suite_log_error_clear
	dashboard_widgets_suite_log_error_errors
	dashboard_widgets_suite_log_error_level
	dashboard_widgets_suite_log_error_path
	
	dashboard_widgets_suite_options_notes_user
	dashboard_widgets_suite_get_options_notes_user
	dashboard_widgets_suite_notes_user_data_add
	dashboard_widgets_suite_notes_user_data_delete
	dashboard_widgets_suite_notes_user_data_edit
	dashboard_widgets_suite_notes_user_data_form
	dashboard_widgets_suite_notes_user_data_get
	dashboard_widgets_suite_notes_user_example
	dashboard_widgets_suite_notes_user_message
	dashboard_widgets_suite_notes_user_style
	dashboard_widgets_suite_notes_user_frontend_data
	dashboard_widgets_suite_notes_user_frontend_view
	dashboard_widgets_suite_notes_format
	dashboard_widgets_suite_note_styles
	
	dashboard_widgets_suite_options_social_box
	dashboard_widgets_suite_get_options_social_box
	dashboard_widgets_suite_social_box_output
	dashboard_widgets_suite_social_box_frontend_data
	
	dashboard_widgets_suite_options_system_info
	dashboard_widgets_suite_get_options_system_info
	
	dashboard_widgets_suite_options_widget_box
	dashboard_widgets_suite_get_options_widget_box
	
	dashboard_widgets_suite_allowed_tags
	dashboard_widgets_suite_editable_roles
	dashboard_widgets_suite_get_date


DWS also provides hooks for [customizing widget names on the Dashboard](https://perishablepress.com/custom-widget-names-dashboard-widgets-suite/).

_[Suggest a hook!](https://plugin-planet.com/support/#contact)_



== Upgrade Notice ==

Visit the WordPress Plugins screen, locate the plugin, and click "Update" :)

__Note:__ Uninstalling the plugin via the WordPress Plugins screen results in the removal of all settings from the WordPress database.



== Frequently Asked Questions ==


**Can you add this widget or that widget?**

Yeah maybe, feel free to [suggest a widget!](https://plugin-planet.com/support/#contact)


**How to customize the names of dashboard widgets?**

It's not a current feature of the plugin, however it is possible using custom functions. Check out this [tutorial](https://perishablepress.com/custom-widget-names-dashboard-widgets-suite/) at Perishable Press.


**How to add a widget that displays all pages?**

To display a Dashboard widget that displays links to all of your pages:

1. Visit the Widget Box tab on the plugin page
2. Check the box for the setting, Enable Widget
3. Select Dashboard Widgets Suite for the Sidebar setting
4. Save changes
5. Visit the Menus screen under the Appearance menu
6. Create a menu that displays all Pages (or whatever links you want)
7. Visit the WP Widgets screen
8. Add your new widget to Dashboard Widgets Suite

Done! You can now visit the Dashboard to view your new menu in the Widget Box.


**How to edit or delete a note?**

Double click on the note itself, and buttons will be displayed, enabling Edit, Delete, etc.


**Who can view the notes?**

For each note, you can specify a level/role user that is allowed to view/read. So any user who has the capabilities of the specified role can read the note. For example, if you set a note level to "Author", then users with role of Author, Editor, or Admin will be able to read.


**How to set a required note format?**

By default, the user may choose which format (text, html, code) they want for each note. To change that, so that only one format is used, add the following code to your theme (or child theme) functions.php, or add via simple [custom plugin](https://digwp.com/2022/02/custom-code-wordpress/):

	function dashboard_widgets_suite_notes_format_custom($field_default) {
		
		return 'text'; // accepts: text, html, code
		
	}
	add_filter('dashboard_widgets_suite_notes_format', 'dashboard_widgets_suite_notes_format_custom');

Change `text` to any accepted format: text, html, or code.


**How to add my own note styles?**

To override the default note styles, add the following code to your theme (or child theme) functions.php, or add via simple [custom plugin](https://digwp.com/2022/02/custom-code-wordpress/):

	function dashboard_widgets_suite_note_styles_custom($css) {
		
		return '#dws-notes-user .dws-notes-user textarea, #dws-notes-user .dws-notes-user-note { font-size: 18px; }';
		
	}
	add_filter('dashboard_widgets_suite_note_styles', 'dashboard_widgets_suite_note_styles_custom');

The returned CSS code is just an example, showing use of DWS existing selectors. You can change the CSS to whatever is necessary.


**How to change path/location of the debug log?**

By default, the WordPress debug file is located at `/wp-content/debug.log`. To change that, add the following code to your theme (or child theme) functions.php, or add via simple [custom plugin](https://digwp.com/2022/02/custom-code-wordpress/):

	function dashboard_widgets_suite_log_debug_path($log) {
	
		return '/whatever/path/debug.log'; 
	
	}
	add_filter('dashboard_widgets_suite_log_debug_path', 'dashboard_widgets_suite_log_debug_path');

Change the path to whatever is necessary.


**Got a question?**

Send any questions or feedback via my [contact form](https://plugin-planet.com/support/#contact)



== Changelog ==

__Thank you__ for using Dashboard Widgets Suite! If you like the plugin, please show support with a [5-star rating &raquo;](https://wordpress.org/support/plugin/dashboard-widgets-suite/reviews/?rate=5#new-post)


**3.4.9 (2025/11/12)**

* Restores `load_i18n()`
* Changes `POST` to `GET` for `wp_remote_post()` test
* Updates plugin settings page
* Improves readme.txt documentation
* Generates new language template
* Tests on WordPress 6.9 (beta)


Full changelog @ [https://plugin-planet.com/wp/changelog/dashboard-widgets-suite.txt](https://plugin-planet.com/wp/changelog/dashboard-widgets-suite.txt)
