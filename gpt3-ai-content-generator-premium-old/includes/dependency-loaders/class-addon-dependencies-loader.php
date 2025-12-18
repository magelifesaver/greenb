<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Addon_Dependencies_Loader
{
    public static function load()
    {
        $addons_path = WPAICG_PLUGIN_DIR . 'classes/addons/';
        $ip_anon_path = $addons_path . 'class-aipkit-ip-anonymization.php';
        if (file_exists($ip_anon_path)) {
            require_once $ip_anon_path;
        }
    }
}
