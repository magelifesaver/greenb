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

class Functions
{
    /**
     * Check version
     *
     * @param string $current
     * @param string $required
     * @return bool
     */
    public static function checkVersion($current, $required, $operator = null)
    {
        if ($required == "*") {
            return true;
        }
        if (empty($operator)) {
            $operator = preg_replace("/[^><=]+/", "", $required);
        }
        $required = rtrim(preg_replace("/[^a-z0-9.-]+/", "", $required), ".");
        return (bool) version_compare($current, $required, $operator);
    }

    /**
     * Render template file
     *
     * @param $file
     * @param $data
     * @param bool $print
     * @return false|string
     */
    public static function renderTemplate($file, $data, $print)
    {
        if (file_exists($file)) {
            ob_start();
            extract($data);
            include $file;
            $output = ob_get_clean();

            if ($print) {
                echo $output;
            }
            return $output;
        }
        return false;
    }
}