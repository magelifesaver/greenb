<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/class-aaa-oc-board-border-filters.php
 * Purpose: Provide filter callbacks that return border colours for each side of
 * the Workflow Board’s order cards. These colours encode customer signals and
 * lifetime spend tiers, and are consumed by the board-card layout to build
 * inline CSS.
 *
 * Definitions:
 *   - Left border: lifetime spend tiers. Customers who have spent
 *     ≥ $2 000 get a black border, those between $1 000 and $1 999 a green border,
 *     and first‑time/low spenders (< $1 000) a blue border.
 *   - Top border: denotes special needs. Enabled via the customer module; by
 *     default it remains transparent here.
 *   - Right border: denotes warnings. Enabled via the customer module; by
 *     default it remains transparent here.
 *   - Bottom border: denotes birthdays. Enabled via the customer module; by
 *     default it remains transparent here.
 *
 * Modules may override these defaults by filtering the same hooks at a later
 * priority. See includes/customer/hooks/aaa-oc-customer-board-borders.php for
 * the customer-specific top/right/bottom borders.
 *
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AAA_OC_Board_Border_Filters' ) ) {

    final class AAA_OC_Board_Border_Filters {

        /**
         * Register all border filters. Called on load.
         */
        public static function init() : void {
            // Left border: base lifetime spend tiers.
            add_filter( 'aaa_oc_board_border_left', [ __CLASS__, 'left_from_lifetime_spend' ], 10, 2 );
            // The core module does not set top/right/bottom; those hooks are
            // provided here as fallbacks in case no module supplies a colour.
            add_filter( 'aaa_oc_board_border_top',    [ __CLASS__, 'noop_border' ], 10, 2 );
            add_filter( 'aaa_oc_board_border_right',  [ __CLASS__, 'noop_border' ], 10, 2 );
            add_filter( 'aaa_oc_board_border_bottom', [ __CLASS__, 'noop_border' ], 10, 2 );

            // Log that the border filters have been registered.  This writes to the
            // debug log only when the global logger is available and helps to
            // confirm that this file was executed on a page load.
            if ( function_exists( 'aaa_oc_log' ) ) {
                aaa_oc_log('[BORDER-FILTERS] core border filters initialized');
            }
        }

        /**
         * Map lifetime spend to a colour.
         *
         * Tiers:
         *   - ≥ $2 000 → #000000 (black) for high‑spend customers.
         *   - ≥ $1 000 → #1bbf5f (green) for mid‑tier spenders.
         *   - > 0      → #4fb3ff (blue) for first‑tier customers.
         *   - else     → leave unchanged (transparent).
         *
         * @param string $color Existing colour supplied by earlier filters.
         * @param array  $ctx   Context array containing an `oi` object.
         * @return string
         */
        public static function left_from_lifetime_spend( string $color, array $ctx ) : string {
            $oi = isset( $ctx['oi'] ) ? (object) $ctx['oi'] : null;
            if ( ! $oi || ! isset( $oi->lifetime_spend ) ) {
                return $color;
            }
            $ls = (float) $oi->lifetime_spend;
            $result = $color;
            if ( $ls >= 2000 ) {
                $result = '#000000';
            } elseif ( $ls >= 1000 ) {
                $result = '#1bbf5f';
            } elseif ( $ls > 0 ) {
                $result = '#4fb3ff';
            }
            // Emit a log entry detailing the calculation.  Captures the lifetime
            // spend, chosen colour and order_id (if available).  If the
            // aaa_oc_log() function is not yet defined this will silently skip.
            if ( function_exists( 'aaa_oc_log' ) ) {
                $oid = $oi && isset( $oi->order_id ) ? $oi->order_id : 'unknown';
                aaa_oc_log('[BORDER-FILTERS] left_from_lifetime_spend: order_id=' . $oid . ' lifetime_spend=' . $ls . ' color=' . $result);
            }
            return $result;
        }

        /**
         * Default callback for top/right/bottom when no module supplies a colour.
         * Simply return the existing colour to preserve transparency.
         *
         * @param string $color Current colour value.
         * @param array  $ctx   Context.
         * @return string
         */
        public static function noop_border( string $color, array $ctx ) : string {
            return $color;
        }
    }

    AAA_OC_Board_Border_Filters::init();
}
