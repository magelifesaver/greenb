<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/hooks/class-aaa-oc-delivery-date-time.php
 * Purpose: Stack Delivery Day/Date + Time under the core left block (Row 2 • Col 1).
 * Hook:    aaa_oc_board_collapsed_left (filter) — runs AFTER core renderer to append two lines
 * Order:   Priority 20 (core default is 10)
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Delivery_Date_Time' ) ) :

final class AAA_OC_Delivery_Date_Time {

	/**
	 * Attach AFTER the core renderer on the same filter.
	 * Filter signature: (bool $handled, array $ctx) : bool
	 */
	public static function init() : void {
		add_filter( 'aaa_oc_board_collapsed_left', [ __CLASS__, 'stack' ], 20, 2 );
	}

	/**
	 * Print two lines only if the core block already rendered ($handled === true).
	 *
	 * @param bool  $handled Whether a previous handler printed the left block
	 * @param array $ctx     Card context; expects $ctx['oi'] (stdClass) with delivery_* fields
	 * @return bool          Always return TRUE so the slot remains claimed
	 */
	public static function stack( $handled, $ctx ) : bool {
		// Only stack after the default core renderer claimed the slot.
		if ( $handled !== true || ! is_array( $ctx ) ) {
			return (bool) $handled;
		}

		$oi = ( isset( $ctx['oi'] ) && is_object( $ctx['oi'] ) ) ? $ctx['oi'] : (object)[];

		// Read strictly from snapshot (no meta, no fallbacks)
		$ts       = isset( $oi->delivery_date_ts )        ? (int) $oi->delivery_date_ts        : 0;
		$ymd      = isset( $oi->delivery_date_formatted ) ? (string) $oi->delivery_date_formatted : '';
		$locale   = isset( $oi->delivery_date_locale )    ? (string) $oi->delivery_date_locale    : '';
		$time     = isset( $oi->delivery_time )           ? (string) $oi->delivery_time           : '';
		$time_rng = isset( $oi->delivery_time_range )     ? (string) $oi->delivery_time_range     : '';

		// If nothing to show, keep quiet (core already printed)
		if ( ( $ts <= 0 && $ymd === '' && $locale === '' ) && $time === '' && $time_rng === '' ) {
			return true;
		}

		// Build human-friendly date label (Today/Tomorrow/Localized date)
		$label = '';
		if ( $ts > 0 ) {
			$now      = current_time( 'timestamp' );
			$today    = date_i18n( 'Y-m-d', $now );
			$target   = date_i18n( 'Y-m-d', $ts );
			$tomorrow = date_i18n( 'Y-m-d', strtotime( $today . ' +1 day' ) );

			if ( $target === $today )       $label = 'Today';
			elseif ( $target === $tomorrow ) $label = 'Tomorrow';
			else                              $label = ( $locale !== '' ) ? $locale : date_i18n( 'D M j, Y', $ts );
		} elseif ( $ymd !== '' ) {
			$now      = current_time( 'timestamp' );
			$today    = date_i18n( 'Y-m-d', $now );
			$tomorrow = date_i18n( 'Y-m-d', strtotime( $today . ' +1 day' ) );

			if ( $ymd === $today )          $label = 'Today';
			elseif ( $ymd === $tomorrow )   $label = 'Tomorrow';
			else                             $label = ( $locale !== '' ) ? $locale : $ymd;
		} else {
			$label = $locale; // last resort
		}

		$time_line = ( $time_rng !== '' ) ? $time_rng : $time;

		// Append under the core left block.
		echo '<div class="aaa-delivery-date-time" style="margin-top:4px;">';
		if ( $label !== '' ) {
			echo '<div class="aaa-delivery-date" style="font-size:.95em;">' . esc_html( $label ) . '</div>';
		}
		if ( $time_line !== '' ) {
			echo '<div class="aaa-delivery-time" style="margin-top:2px;">' . esc_html( $time_line ) . '</div>';
		}
		echo '</div>';

		// Keep the claim as TRUE so nothing else tries to take the slot.
		return true;
	}
}

AAA_OC_Delivery_Date_Time::init();

endif;
