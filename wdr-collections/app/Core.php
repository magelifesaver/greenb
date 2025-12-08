<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App;

use WDR_COL\App\Helpers\Assets;
use WDR_COL\App\Helpers\Functions;
use WDR_COL\App\Helpers\Input;
use WDR_COL\App\Helpers\Plugin;
use WDR_COL\App\Helpers\Request;

defined('ABSPATH') or exit;

class Core
{
    /**
     * Primary instance
     *
     * @var object
     */
    private static $app;

    /**
     * Secondary instances
     *
     * @var object
     */
    public $assets, $config, $input, $request, $plugin;

    /**
     * To load secondary instances
     */
    private function __construct()
    {
        if (is_admin()) {
            $this->request = new Request();
            $this->input = new Input();
        }

        $this->config = new Config();
        $this->assets = new Assets();
        $this->plugin = (object) $this->config->get('plugin');
    }

    /**
     * To get app instance
     *
     * @return object
     */
    public static function instance()
    {
        if (!isset(self::$app)) {
            self::$app = new Core();
        }
        return self::$app;
    }

    /**
     * Bootstrap plugin
     */
    public function bootstrap()
    {
        Setup::init();
        $this->init();
        $this->update();
    }

    /**
     * Init plugin
     */
    private function init()
    {
        add_action('init', function () {
            $this->initI18n();
            if ($this->checkDependencies()) {
                $this->initHooks();
            }
        }, 10);
    }

    /**
     * Update plugin
     */
    private function update()
    {
        add_action('advanced_woo_discount_rules_after_initialize', function () {
            $updater = new \WDR_COL\App\Helpers\Update();
            $updater->initHooks();
            $updater->runUpdater();
        });
    }

    /**
     * Check dependencies
     *
     * @return bool
     */
    private function checkDependencies() {
        return Plugin::checkDependencies();
    }

    /**
     * Init plugin hooks
     */
    private function initHooks()
    {
        Route::hooks();
    }

    /**
     * Init plugin internationalization
     */
    private function initI18n()
    {
        load_plugin_textdomain('wdr-collections', false, dirname(plugin_basename(WDR_COL_PLUGIN_FILE)) . '/i18n/languages');
    }

    /**
     * View
     *
     * @param $path
     * @param $data
     * @param bool $print
     * @return false|string
     */
    public function view($path, $data, $print = true)
    {
        $file = WDR_COL_PLUGIN_PATH . '/app/Views/' . $path . '.php';
        return Functions::renderTemplate($file, $data, $print);
    }

    /**
     * Template
     *
     * @param $file_or_path
     * @param $data
     * @param bool $print
     * @return false|string
     */
    public function template($file_or_path, $data, $print = true)
    {
        if (strpos($file_or_path, '.php') !== false || strpos($file_or_path, '.html') !== false) {
            $filepath = $file_or_path;
        } else {
            $filepath = $file_or_path . '.php';
        }

        $plugin_slug = $this->config->get('plugin.slug', 'wdr-collections');
        $override_file = get_theme_file_path($plugin_slug . '/' . $filepath);
        if (file_exists($override_file)) {
            $file = $override_file;
        } else {
            $file = WDR_COL_PLUGIN_PATH . '/templates/' . $filepath;
        }
        return Functions::renderTemplate($file, $data, $print);
    }
}