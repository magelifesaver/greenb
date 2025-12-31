<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/hooks/aaa-oc-customer-board-borders.php
 * Purpose: Provide customer-related border colours (birthday, warnings, special needs) to the board shell.
 *
 * This version updates the original implementation to correctly retrieve
 * saved settings from the `aaa_oc_options` table and to read the
 * appropriate fields from the order-index context. The prior code
 * incorrectly treated the fallback argument to `aaa_oc_get_option()` as
 * the option scope and looked for fields (`customer_special_needs` and
 * `customer_warning_flag`) that do not exist on the order-index. As a
 * result, border colours never appeared even when options were enabled
 * and customer data was present. This replacement fixes those issues.
 *
 * Definitions:
 *   - Top border: special needs indicator. Enabled via the customer
 *     settings tab. When enabled and a customer’s order-index row
 *     contains a non-empty `customer_special_needs_text` value (or
 *     legacy `customer_special_needs` fallback), the top border is
 *     coloured using the saved option.
 *   - Right border: warnings indicator. Enabled via the customer
 *     settings tab. When enabled and a customer’s order-index row
 *     contains a non-empty `customer_warnings_text` value (or legacy
 *     `customer_warning_flag`), the right border is coloured using
 *     the saved option.
 *   - Bottom border: birthday indicator. Enabled via the customer
 *     settings tab. When enabled and the `lkd_birthday` field on the
 *     order-index row matches today’s month/day, the bottom border
 *     is coloured using the saved option.
 *
 * Version: 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the options helper is available. Load it on demand to avoid
// fatal errors if the core options class hasn’t been loaded yet.
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
    require_once dirname( __FILE__, 4 ) . '/core/options/class-aaa-oc-options.php';
    AAA_OC_Options::init();
}

/**
 * Retrieve a customer-scoped option with a default fallback.
 *
 * The original implementation of `aaa_oc_customer_opt()` incorrectly
 * passed the fallback value as the scope to `aaa_oc_get_option()`. That
 * caused all reads to silently fall back to the default. This helper
 * always uses the 'customer' scope and honours the provided default.
 *
 * @param string $key     Option key.
 * @param mixed  $default Fallback value if the option is not set.
 * @return mixed
 */
function aaa_oc_customer_get( string $key, $default ) {
    if ( function_exists( 'aaa_oc_get_option' ) ) {
        $val = aaa_oc_get_option( $key, 'customer', $default );
        return $val !== '' ? $val : $default;
    }
    return $default;
}

/**
 * Determine if the given order-index context has special needs set.
 *
 * Looks for `customer_special_needs_text` first (populated by the
 * customer indexer) and falls back to `customer_special_needs` for
 * backwards compatibility. Returns true if the value is non-empty and
 * not equal to 'no'.
 *
 * @param object $oi Order-index snapshot as an object.
 * @return bool
 */
function aaa_oc_customer_has_special_needs( $oi ) : bool {
    if ( isset( $oi->customer_special_needs_text ) ) {
        $txt = (string) $oi->customer_special_needs_text;
        return trim( $txt ) !== '' && strtolower( $txt ) !== 'no';
    }
    if ( isset( $oi->customer_special_needs ) ) {
        $flag = (string) $oi->customer_special_needs;
        return trim( $flag ) !== '' && strtolower( $flag ) !== 'no';
    }
    return false;
}

/**
 * Determine if the given order-index context has warnings set.
 *
 * Looks for `customer_warnings_text` first (populated by the customer
 * indexer) and falls back to `customer_warning_flag` for backwards
 * compatibility. Returns true if the value is non-empty and not equal
 * to 'no'.
 *
 * @param object $oi Order-index snapshot as an object.
 * @return bool
 */
function aaa_oc_customer_has_warnings( $oi ) : bool {
    if ( isset( $oi->customer_warnings_text ) ) {
        $txt = (string) $oi->customer_warnings_text;
        return trim( $txt ) !== '' && strtolower( $txt ) !== 'no';
    }
    if ( isset( $oi->customer_warning_flag ) ) {
        $flag = (string) $oi->customer_warning_flag;
        return trim( $flag ) !== '' && strtolower( $flag ) !== 'no';
    }
    return false;
}

// TOP border (Special Needs)
add_filter( 'aaa_oc_board_border_top', function ( $color, $ctx ) {
    // Check if top border is globally enabled for customers.
    $enabled = aaa_oc_customer_get( 'customer_border_top_enabled', 'yes' );
    if ( $enabled !== 'yes' ) {
        return 'transparent';
    }
    // Retrieve the hex colour from options (default WordPress admin blue).
    $hex = sanitize_hex_color( aaa_oc_customer_get( 'customer_border_top_color', '#0073aa' ) );
    // Extract order-index object from context.
    $oi = isset( $ctx['oi'] ) ? $ctx['oi'] : null;
    if ( is_array( $oi ) ) {
        $oi = (object) $oi;
    }
    if ( ! $oi || ! aaa_oc_customer_has_special_needs( $oi ) ) {
        return 'transparent';
    }
    // Use the configured hex colour when special needs are present.
    return $hex;
}, 10, 2 );

// RIGHT border (Warnings)
add_filter( 'aaa_oc_board_border_right', function ( $color, $ctx ) {
    // Check if right border is enabled.
    $enabled = aaa_oc_customer_get( 'customer_border_right_enabled', 'yes' );
    if ( $enabled !== 'yes' ) {
        return 'transparent';
    }
    $hex = sanitize_hex_color( aaa_oc_customer_get( 'customer_border_right_color', '#cc0000' ) );
    $oi = isset( $ctx['oi'] ) ? $ctx['oi'] : null;
    if ( is_array( $oi ) ) {
        $oi = (object) $oi;
    }
    if ( ! $oi || ! aaa_oc_customer_has_warnings( $oi ) ) {
        return 'transparent';
    }
    return $hex;
}, 10, 2 );

// BOTTOM border (Birthday)
add_filter( 'aaa_oc_board_border_bottom', function ( $color, $ctx ) {
    // Check if bottom border is enabled.
    $enabled = aaa_oc_customer_get( 'customer_border_bottom_enabled', 'yes' );
    if ( $enabled !== 'yes' ) {
        return 'transparent';
    }
    $hex = sanitize_hex_color( aaa_oc_customer_get( 'customer_border_bottom_color', '#ff00aa' ) );
    $oi = isset( $ctx['oi'] ) ? $ctx['oi'] : null;
    if ( is_array( $oi ) ) {
        $oi = (object) $oi;
    }
    if ( ! $oi ) {
        return 'transparent';
    }
    // Determine birthday: prefer lkd_birthday field (YYYY-MM-DD), fallback to customer_dob.
    $dob = null;
    if ( isset( $oi->lkd_birthday ) ) {
        $dob = (string) $oi->lkd_birthday;
    } elseif ( isset( $oi->customer_dob ) ) {
        $dob = (string) $oi->customer_dob;
    }
    if ( ! $dob ) {
        return 'transparent';
    }
    $dob_ts = strtotime( $dob );
    if ( ! $dob_ts ) {
        return 'transparent';
    }
    // Compare month-day of dob with today.
    $today = current_time( 'timestamp' );
    if ( date( 'm-d', $dob_ts ) !== date( 'm-d', $today ) ) {
        return 'transparent';
    }
    return $hex;
}, 10, 2 );

// Optional: log initialisation once to aid debugging.
if ( function_exists( 'aaa_oc_log' ) ) {
    aaa_oc_log( '[CUSTOMER-BORDERS] Customer border hooks registered' );
}
