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

namespace WDR_COL\App\Helpers;

defined('ABSPATH') or exit;

use WDR_COL\App\Config;

class Update
{
    /**
     * Required variables
     *
     * @var string
     */
    private $remote_url, $plugin_slug;

    /**
     * Set data
     */
    public function __construct()
    {
        $this->remote_url = "https://my.flycart.org/";
        $this->plugin_slug = Config::get('plugin.slug', 'wdr-collections');
    }

    /**
     * Init hooks
     */
    public function initHooks()
    {
        add_filter('puc_request_info_result-' . $this->plugin_slug, [$this, 'loadPluginContent'], 10, 2);
    }

    /**
     * Load plugin content
     *
     * @param $pluginInfo
     * @param $result
     * @return mixed
     */
    function loadPluginContent($pluginInfo, $result)
    {
        if (is_object($pluginInfo)) {
            if (isset($pluginInfo->sections)) {
                $section = $pluginInfo->sections;
                if (empty($section['description'])) {
                    $section['description'] = '';
                    $section['changelog'] = '';
                    $pluginInfo->sections = $section;
                }
            } else {
                $pluginInfo->sections = array(
                    'description' => '',
                    'changelog' => '',
                );
            }
            $pluginInfo->name = "WDR Collections";
            $pluginInfo->author = "Flycart";
            $pluginInfo->last_updated = "2023-02-09 05:30";
            $pluginInfo->active_installs = 1000;
        }
        return $pluginInfo;
    }

    /**
     * Get discount rules license key
     */
    protected function getLicenseKey()
    {
        $config = \Wdr\App\Controllers\Configuration::getInstance();
        return $config->getConfig('licence_key', '');
    }

    /**
     * To get update URL
     *
     * @return string
     */
    protected function getUpdateURL()
    {
        $licence_key = $this->getLicenseKey();
        if (empty($licence_key)) {
            return null;
        }
        $fields = array(
            'wpaction' => 'updatecheck',
            'wpslug' => urlencode($this->plugin_slug),
            'pro' => 0,
            'dlid' => $licence_key
        );
        return $this->remote_url . '?' . http_build_query($fields);
    }

    /**
     * To run the updater
     */
    public function runUpdater()
    {
        $update_url = $this->getUpdateURL();
        if (!empty($update_url) && class_exists('Puc_v4_Factory')) {
            \Puc_v4_Factory::buildUpdateChecker($update_url, WDR_COL_PLUGIN_FILE, $this->plugin_slug);
        }
    }
}