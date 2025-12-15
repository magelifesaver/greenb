=== MyServerInfo - Memory Usage, PHP Version, Memory Limit, Execution Time, CPU Usage, Disk Usage ===
Contributors: antonphp
Tags: php version, memory limit, CPU Usage, Disk Usage, memory
Tested up to: 6.8
Stable tag: 1.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays Usage (CPU , Disk, Memory), PHP and MySQL Version, WP Memory Limit, PHP Execution Time, Max Input Vars, IP Address, Uptime, Timezone. 

== Description ==

My Server Info is a lightweight plugin that displays key server and site information in your WordPress admin panel. It shows details like:

- PHP Version
- MySQL Version
- WordPress Memory Limit
- PHP Execution Time
- PHP Max Input Vars
- PHP post_max_size
- PHP upload_max_filesize
- Site IP Address
- Site Time and Timezone
- CPU Usage (Average over 1 minute)
- Disk Usage
- Memory Usage
- Server Uptime

**PHP Version**  
PHP Version indicates the current PHP version running on your server. WordPress relies on PHP for its core functionality, and using a supported version (7.4 or higher) ensures better performance, security, and compatibility with themes and plugins.

**MySQL Version**  
MySQL Version shows the version of the MySQL database your WordPress site is using. A compatible MySQL version (5.7 or higher) is essential for efficient data management, faster queries, and overall site stability.

**WordPress Memory Limit**  
WordPress Memory Limit defines the maximum amount of memory allocated for WordPress operations. A higher memory limit (256M or more) allows for smoother performance, especially when using resource-intensive plugins or handling large websites.

**PHP Execution Time**  
PHP Execution Time sets the maximum time a PHP script is allowed to run. Increasing this limit (300 seconds or more) helps prevent timeout errors during lengthy operations, such as bulk uploads or complex plugin processes.

**PHP Max Input Vars**  
PHP Max Input Vars specifies the maximum number of input variables your server can handle. Setting this to a higher value (3000 or more) ensures that large forms, like those in theme or plugin settings, function correctly without data loss.

**PHP post_max_size**  
PHP post_max_size determines the maximum size of data that can be submitted via POST requests. Setting this to at least 64M allows for uploading larger files through forms, such as media uploads or bulk data submissions, without encountering size limitations.

**PHP upload_max_filesize**  
PHP upload_max_filesize defines the maximum size of an individual file that can be uploaded through PHP. A higher limit (64M or more) is important for WordPress sites that handle large media files, plugins, or theme uploads, ensuring users can upload necessary files without restrictions.

**Site IP Address**  
Site IP Address displays your website’s public IP address. Knowing your site's IP is useful for configuring DNS settings, troubleshooting connectivity issues, and enhancing site security through access controls.

**Site Time and Timezone**  
Site Time and Timezone reflect the current date, time, and timezone configured in your WordPress settings. Accurate time settings are crucial for scheduling posts, managing cron jobs, and ensuring consistency across your site's content and activities.

**CPU Usage (Average over 1 minute)**  
CPU Usage provides an approximate percentage of CPU utilization averaged over the past minute. This helps in monitoring server performance and identifying potential issues related to high CPU load.

**Disk Usage**  
Disk Usage shows the percentage of disk space used on your server. Monitoring disk usage helps in managing storage resources effectively and preventing issues related to insufficient disk space.

**Admin Bar Integration**  
Under each progress bar, administrators can select checkboxes to add specific parameters to the WordPress admin bar. The available options are:

- **Memory Usage:** Displays as `MEM: X%`
- **CPU Usage:** Displays as `AVG CPU: Y%`
- **Disk Usage:** Displays as `Disk: Z%`

This feature allows quick access to essential server metrics directly from the admin bar, enhancing monitoring efficiency.

**Server Uptime**
Displays the server’s uptime by reading the /proc/uptime file on Linux systems and formatting it into days, hours, minutes, and seconds. On unsupported systems (e.g., Windows), it will show “Unavailable”.

== Installation ==

1. Upload the `My Server Info` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the 'Plugins' menu in WordPress.
3. Go to "My Server Info" in the WordPress admin panel to view server information and configure settings.

== Frequently Asked Questions ==

= What kind of PHP information does this plugin display? =

This plugin shows details like the PHP Version, WP Memory Limit, PHP Execution Time, PHP Max Input Vars, PHP post_max_size, PHP upload_max_filesize, along with CPU Usage and Disk Usage. It also includes recommended values for each parameter.

= Can I customize the data displayed by this plugin? =

Yes, you can customize which parameters are displayed in the WordPress admin bar. Under each progress bar, there are checkboxes labeled "Add to admin bar." Select the parameters you want to appear in the admin bar and save the settings.

= How does CPU Usage work in this plugin? =

CPU Usage provides an approximate percentage of CPU utilization averaged over the past minute. It normalizes the load average by the number of CPU cores to estimate the CPU usage percentage. Please note that this is an approximate value and may vary based on server configurations.

= What is Disk Usage and how does it affect my site? =

Disk Usage indicates the percentage of disk space used on your server. Monitoring disk usage helps in managing storage resources effectively, ensuring that your site has sufficient disk space for operations like media uploads, plugin installations, and database growth. High disk usage can lead to performance issues and hinder the ability to upload new content or plugins.

= What PHP versions does this plugin support, and what do they include? =

This plugin supports PHP 7.2 and above, up to the upcoming 8.5 release. Below is a brief overview of these versions:

* **PHP Version 7.2** (Released: November 30, 2017)  
  Introduced the `object` type, added Libsodium for cryptography, and deprecated several legacy features.

* **PHP Version 7.3** (Released: December 6, 2018)  
  Added flexible heredoc/nowdoc syntax, the `is_countable()` function, and the `JSON_THROW_ON_ERROR` flag.

* **PHP Version 7.4** (Released: November 28, 2019)  
  Brought arrow functions, typed properties, and various performance improvements.

* **PHP Version 8.0** (Released: November 26, 2020)  
  Introduced named arguments, union types, the `match` expression, and a JIT (Just-In-Time) compiler.

* **PHP Version 8.1** (Released: November 25, 2021)  
  Added enums, read-only properties, and first-class callable syntax, improving code clarity and performance.

* **PHP Version 8.2** (Released: December 8, 2022)  
  Introduced read-only classes, further refined JIT, and deprecated dynamic properties by default.

* **PHP Version 8.3** (Released: November 23, 2023)  
  Continued performance enhancements, added a `json_validate()` function, and introduced more read-only features.

* **PHP Versions 8.4 & 8.5**  
  Planned for future releases with expected incremental improvements and new features. Actual release dates may vary.

= What is post_max_size and how does it affect my site? =

post_max_size is a PHP configuration directive that sets the maximum amount of data (including file uploads) that can be sent via the POST method. This affects features like uploading media files, submitting forms, and any other operation that relies on POST requests. If you often handle large files or forms, you may need to increase the post_max_size value in your server’s PHP settings to avoid errors and ensure smooth uploads.

== Screenshots ==

1. Resource Usage
2. My Server Info on the admin plugins page.
3. Resource Usage - admin bar

== Changelog ==

= 1.0 =
* Initial release of PHP Info.

= 1.1 =
* Added MySQL version to the server information table.
* Added Site Data table with IP Address and Timezone.

= 1.2 =
* Added PHP post_max_size display.
* Added PHP Version FAQ.

= 1.3 =
* Added PHP upload_max_filesize display.
* Updated plugin description.

= 1.4 - January 14, 2025 =
* Introduced CPU Usage metric with average over 1 minute.
* Added Disk Usage metric to monitor disk space.
* Added progress bars for Memory Usage, CPU Usage, and Disk Usage.
* Added checkboxes under each progress bar to allow administrators to select which parameters to display in the admin bar.
* Enabled display of selected parameters in the admin bar as `MEM: X%`, `AVG CPU: Y%`, and `Disk: Z%`.

= 1.5 - February 18, 2025 =
* Added new **Server Uptime** feature to display the server's runtime.

= 1.5.1 - November 18, 2025 =
* Added feedback