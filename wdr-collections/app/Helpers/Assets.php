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

use WDR_COL\App\Config;

defined('ABSPATH') or exit;

class Assets
{
    /**
     * Styles
     *
     * @var array
     */
    public $styles = [];

    /**
     * Scripts
     *
     * @var array
     */
    public $scripts = [];

    /**
     * Prefix
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Version
     *
     * @var string|null
     */
    public $version = null;

    /**
     * Location to enqueue scripts
     *
     * @var array
     */
    protected $locations = [
        'front' => 'wp_enqueue_scripts',
        'admin' => 'admin_enqueue_scripts',
        'login' => 'login_enqueue_scripts',
        'customizer' => 'customize_preview_init',
    ];

    /**
     * Asset
     */
    public function __construct()
    {
        $this->prefix = Config::get('plugin.prefix', 'WDR_COL_');
        $this->version = Config::get('plugin.version', null);
    }

    /**
     * Get asset url
     *
     * @param string $path
     * @return string
     */
    public static function getUrl($path) {
        return plugin_dir_url(WDR_COL_PLUGIN_FILE) . "assets/" . $path;
    }

    /**
     * Check if the file is exists or not
     *
     * @param string $path
     * @return bool
     */
    public static function fileExists($path) {
        return file_exists(WDR_COL_PLUGIN_PATH . "/assets/" . $path);
    }

    /**
     * Load minified file or not
     *
     * @return bool
     */
    protected function loadMinified() {
        return (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true) || Config::get('debug') === true;
    }

    /**
     * Enqueue style
     *
     * @param string $name
     * @param string $file
     * @param array $deps
     * @return Assets
     */
    public function addCss($name, $file, array $deps = [])
    {
        $extension = ".css";
        if ($this->loadMinified() && $this->fileExists("css/" . $file . ".min.css")) {
            $extension = ".min.css";
        }

        $this->styles[$this->prefix . $name] = [
            'src' => $this->getUrl("css/" . $file . $extension),
            'deps' => $deps,
        ];

        return $this;
    }

    /**
     * Dequeue style
     *
     * @param string $name
     * @return Assets
     */
    public function removeCss($name) {
        if (isset($this->styles[$this->prefix . $name])) {
            unset($this->styles[$this->prefix . $name]);
        }

        return $this;
    }

    /**
     * Enqueue script
     *
     * @param string $name
     * @param string $file
     * @param array $data
     * @param array $deps
     * @return Assets
     */
    public function addJs($name, $file, array $data = [], array $deps = [])
    {
        $extension = ".js";
        if (self::loadMinified() && self::fileExists("js/" . $file . ".min.js")) {
            $extension = ".min.js";
        }

        $this->scripts[$this->prefix . $name] = [
            'src' => $this->getUrl("js/" . $file . $extension),
            'data' => $data,
            'deps' => $deps,
        ];

        return $this;
    }

    /**
     * Dequeue script
     *
     * @param string $name
     * @return Assets
     */
    public function removeJs($name)
    {
        if (isset($this->scripts[$this->prefix . $name])) {
            unset($this->scripts[$this->prefix . $name]);
        }

        return $this;
    }

    /**
     * Enqueue scripts
     */
    public function enqueue($location = null) {
        if (is_null($location)) {
            $location = is_admin() ? 'admin' : 'front';
        }

        if (!array_key_exists($location, $this->locations)) {
            throw new \UnexpectedValueException('Expected a valid location on enqueue method');
        }

        $data = [
            'styles' => $this->styles,
            'scripts' => $this->scripts,
            'version' => $this->version,
        ];

        add_action($this->locations[$location], function () use ($data) {
            foreach ($data['styles'] as $name => $style) {
                wp_enqueue_style($name, $style['src'], $style['deps'], $data['version']);
            }

            foreach ($data['scripts'] as $name => $script) {
                wp_enqueue_script($name, $script['src'], $script['deps'], $data['version']);
                if (!empty($script['data'])) {
                    wp_localize_script($name, $name, $script['data']);
                }
            }
        }, 10000);
    }
}