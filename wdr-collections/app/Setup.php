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

use WDR_COL\App\Helpers\Plugin;
use WDR_COL\App\Models\Collections;
use WDR_COL\App\Models\RuleCollections;

defined('ABSPATH') or exit;

class Setup
{
    /**
     * Init setup
     */
    public static function init() {
        register_activation_hook(WDR_COL_PLUGIN_FILE, [__CLASS__, 'activate']);
        register_deactivation_hook(WDR_COL_PLUGIN_FILE, [__CLASS__, 'deactivate']);
        register_uninstall_hook(WDR_COL_PLUGIN_FILE, [__CLASS__, 'uninstall']);
    }

    /**
     * Run plugin activation scripts
     */
    public static function activate()
    {
        //Plugin::checkDependencies(true);
        self::runMigration();
    }

    /**
     * Run plugin activation scripts
     */
    public static function deactivate()
    {
        // silence is golden
    }

    /**
     * Run plugin activation scripts
     */
    public static function uninstall()
    {
        // silence is golden
    }

    /**
     * Run migration
     */
    private static function runMigration()
    {
        $models = [
            new Collections(),
            new RuleCollections(),
        ];

        foreach ($models as $model) {
            $model->create();
        }
    }
}