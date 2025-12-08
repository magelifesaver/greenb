<?php



define('A4B_PLUGIN_PLAN', 'BUSINESS');


define('A4B_PLUGIN_TYPE', 'PRINT');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $_SERVER['HTTPS'] = 'on';
}

define('A4B_PLUGIN_BASE_URL', plugin_dir_url(__FILE__));

define('A4B_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));

define('A4B_SITE_BASE_URL', get_site_url());
