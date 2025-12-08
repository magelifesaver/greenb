<?php

namespace UkrSolution\ProductLabelsPrinting\Updater;

use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class Updater
{
    public function __construct()
    {
        $this->initServer();
    }

    private function initServer()
    {
        add_action('init', function () {
            try {
                $generalSettings = UserSettings::getGeneral();
                $plugin_current_version = '3.4.12';
                $plugin_slug = Variables::$A4B_PLUGIN_BASE_NAME;
                $plugin_remote_path = 'https://www.ukrsolution.com/CheckUpdates/BarcodesForWordpressV3.json';
                $license_user = '3713f12c2a10242bab12a361bcac2ade';
                $license_key = ($generalSettings && isset($generalSettings["lk"])) ? $generalSettings["lk"] : "";
                new WpAutoUpdate($plugin_current_version, $plugin_remote_path, $plugin_slug, $license_user, $license_key);
            } catch (\Throwable $th) {
            }
        });
    }
}
