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

class WP
{
    /**
     * Add admin notice
     *
     * @param string $message
     * @param string $status
     * @return void
     */
    public static function adminNotice($message, $status = "success")
    {
        if (!function_exists('add_action')) return;

        add_action('admin_notices', function () use ($message, $status) {
            ?>
            <div class="notice notice-<?php echo esc_attr($status); ?>">
                <p><?php echo $message; ?></p>
            </div>
            <?php
        }, 1);
    }
}