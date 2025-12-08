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

namespace WDR_COL\App\Controllers;

defined('ABSPATH') or exit;

use WDR_COL\App\Core;

abstract class Controller
{
    /**
     * App instance
     *
     * @var object
     */
    public $app;

    /**
     * To load core instance
     */
    public function __construct()
    {
        $this->app = Core::instance();
    }
}