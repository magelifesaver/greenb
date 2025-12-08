<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/helpers/class-aaa-oc-delivery-metabox.php
 * Purpose: Admin Order Edit meta box (Delivery Date & Time) that saves new tpfw_* keys.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_Delivery_Metabox' ) ) :

final class AAA_OC_Delivery_Metabox {

	const DEBUG_THIS_FILE = true;
	const NONCE_ACTION    = 'aaa_oc_delivery_metabox_save';
	const NONCE_NAME      = 'aaa_oc_delivery_metabox_nonce';

	public static function init() : void {
		add_action( 'add_meta_boxes',        [ __CLASS__, 'register' ] );
		add_action( 'save_post_shop_order',  [ __CLASS__, 'save' ], 20, 3 );
	}

	public static function register() : void {
		add_meta_box(
			'aaa-oc-delivery-metabox',
			__( 'Delivery Date & Time', 'aaa-oc' ),
			[ __CLASS__, 'render' ],
			'shop_order',
			'side',
			'high'
		);
	}

	public static function render( WP_Post $post ) : void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		// Read NEW keys first (authoritative), else fallback to legacy if present.
		$site_tz  = wp_timezone();

		$ts       = (int) get_post_meta( $post->ID, 'tpfw_picked_time_timestamp', true );
		$plain    =      get_post_meta( $post->ID, 'tpfw_picked_time', true ); // "Y-m-d h:i am/pm"
		$to_label =      get_post_meta( $post->ID, 'tpfw_picked_time_range_end_localized', true );

		if ( ! $ts ) {
			$legacy_ts = (int) get_post_meta( $post->ID, 'delivery_date', true );
			if ( $legacy_ts ) { $ts = $legacy_ts; }
		}

		// Defaults
		$dt    = $ts ? ( new DateTime( 'now', $site_tz ) )->setTimestamp( $ts ) : new DateTime( 'now', $site_tz );
		$date  = $dt->format( 'Y-m-d' );   // for <input type="date">
		$fromH = $dt->format( 'H:i' );     // "HH:MM" 24h
		$toH   = $to_label ? self::normalize_to_24h( $date, $to_label, $site_tz ) : '';

		// If $toH empty, default to +45 minutes from fromH (bounded to 22:00).
		if ( ! $toH ) {
			$copy = clone $dt;
			$copy->modify('+45 minutes');
			$limit = ( clone $dt )->setTime(22,0,0);
			if ( $copy > $limit ) { $copy = $limit; }
			$toH = $copy->format('H:i');
		}

		?>
		<p><strong><?php esc_html_e( 'Delivery date', 'aaa-oc' ); ?></strong><br>
			<input type="date" name="aaa_delivery_date" value="<?php echo esc_attr( $date ); ?>" style="width:100%;">
		</p>

		<p><strong><?php esc_html_e( 'From time', 'aaa-oc' ); ?></strong><br>
			<select name="aaa_delivery_time_from" style="width:100%;">
				<?php echo self::build_time_options( '10:00', '22:00', '15 minutes', $fromH ); ?>
			</select>
		</p>

		<p><strong><?php esc_html_e( 'To time', 'aaa-oc' ); ?></strong><br>
			<select name="aaa_delivery_time_to" style="width:100%;">
				<?php echo self::build_time_options( '10:00', '22:00', '15 minutes', $toH ); ?>
			</select>
		</p>
		<?php
	}

	public static function save( int $post_id, WP_Post $post, bool $update ) : void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_shop_order', $post_id ) ) { return; }

		$site_tz   = wp_timezone();
		$date_in   = isset( $_POST['aaa_delivery_date'] ) ? sanitize_text_field( wp_unslash( $_POST['aaa_delivery_date'] ) ) : '';
		$from_in   = isset( $_POST['aaa_delivery_time_from'] ) ? sanitize_text_field( wp_unslash( $_POST['aaa_delivery_time_from'] ) ) : '';
		$to_in     = isset( $_POST['aaa_delivery_time_to'] ) ? sanitize_text_field( wp_unslash( $_POST['aaa_delivery_time_to'] ) ) : '';

		if ( empty( $date_in ) || empty( $from_in ) ) {
			return; // require at least date + from
		}

		try {
			// Compose full "picked time" in site TZ.
			$from_dt = new DateTime( sprintf( '%s %s', $date_in, $from_in ), $site_tz );
			$ts      = $from_dt->getTimestamp();

			// Compose range-end label in site TZ.
			$to_dt   = ! empty( $to_in ) ? new DateTime( sprintf( '%s %s', $date_in, $to_in ), $site_tz ) : null;

			// Build new-key values (authoritative)
			$tpfw_mode         = get_post_meta( $post_id, 'tpfw_delivery_mode', true );
			if ( empty( $tpfw_mode ) ) { $tpfw_mode = 'delivery'; }

			$tpfw_picked_time              = $from_dt->format( 'Y-m-d g:i a' );         // "2025-10-29 1:15 pm"
			$tpfw_picked_time_localized    = $from_dt->format( 'F j, Y g:i a' );        // "October 29, 2025 1:15 pm"
			$tpfw_picked_time_ts           = $ts;
			$tpfw_range_end_localized      = $to_dt ? $to_dt->format( 'g:i a' ) : '';   // "2:00 pm"

			// Save tpfw_* (writes only these; legacy will be mirrored by your bridge file)
			update_post_meta( $post_id, 'tpfw_delivery_mode',                        $tpfw_mode );
			update_post_meta( $post_id, 'tpfw_picked_time',                          $tpfw_picked_time );
			update_post_meta( $post_id, 'tpfw_picked_time_localized',                $tpfw_picked_time_localized );
			update_post_meta( $post_id, 'tpfw_picked_time_timestamp',                $tpfw_picked_time_ts );
			update_post_meta( $post_id, 'tpfw_picked_time_range_end_localized',      $tpfw_range_end_localized );

			self::log( $post_id, sprintf(
				'Saved tpfw_* (from:%s to:%s date:%s ts:%d)',
				$from_dt->format('g:i a'),
				$tpfw_range_end_localized ?: 'n/a',
				$from_dt->format('Y-m-d'),
				$tpfw_picked_time_ts
			) );

		} catch ( Exception $e ) {
			self::log( $post_id, 'Save error: ' . $e->getMessage() );
		}
	}

	/** Utilities */

	private static function build_time_options( string $start, string $end, string $step, string $selected_24h ) : string {
		$site_tz  = wp_timezone();
		$out      = '';
		$cursor   = new DateTime( $start, $site_tz );
		$limit    = new DateTime( $end,   $site_tz );
		while ( $cursor <= $limit ) {
			$val = $cursor->format( 'H:i' );   // "HH:MM"
			$lbl = $cursor->format( 'g:i a' ); // "h:mm am/pm"
			$sel = ( $val === $selected_24h ) ? ' selected' : '';
			$out .= '<option value="' . esc_attr( $val ) . '"' . $sel . '>' . esc_html( $lbl ) . '</option>';
			$cursor->modify( '+'.$step );
		}
		return $out;
	}

	private static function normalize_to_24h( string $date_y_m_d, string $label_time, DateTimeZone $tz ) : string {
		$label_time = trim( strtolower( preg_replace( '/\s+/', ' ', $label_time ) ) ); // e.g., "11:45 am"
		$dt = DateTime::createFromFormat( 'Y-m-d g:i a', $date_y_m_d . ' ' . $label_time, $tz );
		if ( $dt instanceof DateTime ) {
			return $dt->format( 'H:i' );
		}
		// fallback: try strtotime
		$ts = strtotime( $label_time );
		return $ts ? wp_date( 'H:i', $ts, $tz ) : '';
	}

	private static function log( int $order_id, string $msg ) : void {
		if ( self::DEBUG_THIS_FILE && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[AAA-OC][DELIVERY-MB] #%d %s', $order_id, $msg ) );
		}
	}
}

AAA_OC_Delivery_Metabox::init();

endif;
