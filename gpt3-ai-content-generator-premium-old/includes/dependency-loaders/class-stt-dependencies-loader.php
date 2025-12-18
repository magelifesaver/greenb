<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Stt_Dependencies_Loader
{
    public static function load()
    {
        $stt_base_path = WPAICG_PLUGIN_DIR . 'classes/stt/';
        $paths = [
            'class-aipkit-stt-manager.php', 'interface-aipkit-stt-provider-strategy.php',
            'class-aipkit-stt-base-provider-strategy.php', 'class-aipkit-stt-provider-strategy-factory.php',
            'class-aipkit-stt-openai-provider-strategy.php', 'class-aipkit-stt-google-provider-strategy.php',
            'class-aipkit-stt-azure-provider-strategy.php',
        ];
        foreach ($paths as $file) {
            $full_path = $stt_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
