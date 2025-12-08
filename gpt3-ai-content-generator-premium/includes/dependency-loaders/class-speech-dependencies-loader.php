<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Speech_Dependencies_Loader
{
    public static function load()
    {
        $speech_base_path = WPAICG_PLUGIN_DIR . 'classes/speech/';
        $paths = [
            'class-aipkit-speech-manager.php', 'interface-aipkit-tts-provider-strategy.php',
            'class-aipkit-tts-base-provider-strategy.php', 'class-aipkit-tts-provider-strategy-factory.php',
            'class-aipkit-tts-openai-provider-strategy.php', 'class-aipkit-tts-google-provider-strategy.php',
            'class-aipkit-tts-elevenlabs-provider-strategy.php',
        ];
        foreach ($paths as $file) {
            $full_path = $speech_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
