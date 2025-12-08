<?php
/**
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/includes/class-adbd-admin.php
 * Version: 0.1.0
 * Purpose: Admin UI & settings under WooCommerce. Registers the Dispatcher submenu,
 *          fields (Maps JS key, server key placeholder, origin, radius, order statuses,
 *          per-status pin colors), enqueues assets, localizes config, and renders the
 *          combined page (settings + map + tree).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ADBD_Admin {
	const OPTION_GROUP = 'adbd_settings_group';
	const OPTION_NAME  = 'adbd_settings';
	const PAGE_SLUG    = 'adbd-dispatcher';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	/** Register submenu under WooCommerce */
	public static function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Delivery Dispatcher', 'adbd' ),
			__( 'Delivery Dispatcher', 'adbd' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/** Register and sanitize settings */
	public static function register_settings() {
		$defaults = [
			'client_api_key' => '',
			'server_api_key' => '', // reserved for future Directions/Distance use
			'origin_json'    => '[{"id":"default","lat":34.097,"lng":-117.648,"mode":"driving"}]',
			'radius_miles'   => 30,
			'order_statuses' => [ 'processing' ], // slugs without wc- prefix
			'status_colors'  => [],               // map: status_slug => #hex
		];

		if ( ! get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $defaults );
		}

		register_setting( self::OPTION_GROUP, self::OPTION_NAME, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
			'default'           => $defaults,
		] );

		// Sections
		add_settings_section( 'adbd_main',   __( 'General', 'adbd' ),        '__return_false', self::PAGE_SLUG );
		add_settings_section( 'adbd_status', __( 'Order Statuses', 'adbd' ), '__return_false', self::PAGE_SLUG );

		// Fields — General
		add_settings_field( 'client_api_key', __( 'Maps JavaScript API key (client/referrer)', 'adbd' ), [ __CLASS__, 'field_client_key' ], self::PAGE_SLUG, 'adbd_main' );
		add_settings_field( 'server_api_key', __( 'Directions/Distance API key (server/IP)', 'adbd' ),   [ __CLASS__, 'field_server_key' ], self::PAGE_SLUG, 'adbd_main' );
		add_settings_field( 'origin_json',    __( 'Origins JSON', 'adbd' ),                                 [ __CLASS__, 'field_origin_json' ], self::PAGE_SLUG, 'adbd_main' );
		add_settings_field( 'radius_miles',   __( 'Radius (miles)', 'adbd' ),                                [ __CLASS__, 'field_radius' ],      self::PAGE_SLUG, 'adbd_main' );

		// Fields — Statuses + Colors
		add_settings_field( 'order_statuses', __( 'Show orders with status…', 'adbd' ), [ __CLASS__, 'field_statuses' ],     self::PAGE_SLUG, 'adbd_status' );
		add_settings_field( 'status_colors',  __( 'Pin colors by status', 'adbd' ),      [ __CLASS__, 'field_status_colors' ], self::PAGE_SLUG, 'adbd_status' );
	}

	/** Sanitize all settings */
	public static function sanitize_settings( $input ) {
		$out = [];

		$out['client_api_key'] = isset( $input['client_api_key'] ) ? sanitize_text_field( $input['client_api_key'] ) : '';
		$out['server_api_key'] = isset( $input['server_api_key'] ) ? sanitize_text_field( $input['server_api_key'] ) : '';
		$out['origin_json']    = isset( $input['origin_json'] )    ? wp_kses_post( $input['origin_json'] ) : '';
		$out['radius_miles']   = isset( $input['radius_miles'] )   ? floatval( $input['radius_miles'] ) : 30;
		if ( $out['radius_miles'] <= 0 ) $out['radius_miles'] = 30;

		// Order statuses: store without wc- prefix
		$statuses = isset( $input['order_statuses'] ) && is_array( $input['order_statuses'] ) ? $input['order_statuses'] : [ 'processing' ];
		$statuses = array_map( 'sanitize_text_field', $statuses );
		$statuses = array_values( array_filter( array_unique( array_map( function( $s ) {
			return ltrim( $s, 'wc-' );
		}, $statuses ) ) ) );
		if ( empty( $statuses ) ) $statuses = [ 'processing' ];
		$out['order_statuses'] = $statuses;

		// Status colors: keep only valid hex for chosen statuses
		$colors_in = isset( $input['status_colors'] ) && is_array( $input['status_colors'] ) ? $input['status_colors'] : [];
		$out['status_colors'] = [];
		foreach ( $statuses as $slug ) {
			if ( ! isset( $colors_in[ $slug ] ) ) continue;
			$hex = strtoupper( preg_replace( '/[^#0-9A-Fa-f]/', '', (string) $colors_in[ $slug ] ) );
			// Normalize to #RRGGBB
			if ( preg_match( '/^#?[0-9A-F]{6}$/i', $hex ) ) {
				if ( $hex[0] !== '#' ) $hex = '#' . $hex;
				$out['status_colors'][ $slug ] = $hex;
			}
		}

		return $out;
	}

	/** Enqueue styles/scripts only on our page and localize config */
	public static function enqueue( $hook ) {
		if ( $hook !== 'woocommerce_page_' . self::PAGE_SLUG ) return;

		// Adjust these paths if you keep assets elsewhere
		wp_enqueue_style(  'adbd-admin', ADBD_PLUGIN_URL . 'assets/admin.css', [], ADBD_VERSION );
		wp_enqueue_script( 'adbd-admin', ADBD_PLUGIN_URL . 'assets/admin-page.js', [ 'jquery' ], ADBD_VERSION, true );

		$settings = get_option( self::OPTION_NAME, [] );

		wp_localize_script( 'adbd-admin', 'ADBD', [
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'rest'          => [ 'orders' => esc_url_raw( rest_url( 'adbd/v1/orders' ) ) ],
			'origin'        => self::get_primary_origin(),
			'radiusMiles'   => floatval( $settings['radius_miles'] ?? 30 ),
			'clientApiKey'  => $settings['client_api_key'] ?? '',
			'statusColors'  => isset( $settings['status_colors'] ) && is_array( $settings['status_colors'] ) ? $settings['status_colors'] : [],
		] );
	}

	/** Read and normalize the first origin from JSON */
	public static function get_primary_origin() {
		$settings = get_option( self::OPTION_NAME, [] );
		$json = $settings['origin_json'] ?? '';
		$origin = [ 'lat' => 34.097, 'lng' => -117.648, 'id' => 'default' ];
		if ( $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) && ! empty( $decoded[0]['lat'] ) && ! empty( $decoded[0]['lng'] ) ) {
				$origin['lat'] = floatval( $decoded[0]['lat'] );
				$origin['lng'] = floatval( $decoded[0]['lng'] );
				$origin['id']  = sanitize_text_field( $decoded[0]['id'] ?? 'default' );
			}
		}
		return $origin;
	}

	/** Render combined settings + app page */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return; ?>
		<div class="wrap adbd-wrap">
			<h1><?php esc_html_e( 'Delivery Dispatcher', 'adbd' ); ?></h1>

			<form method="post" action="options.php" class="adbd-form">
				<?php
					settings_fields( self::OPTION_GROUP );
					do_settings_sections( self::PAGE_SLUG );
					submit_button();
				?>
			</form>

			<div id="adbd-app" class="adbd-app">
				<div class="adbd-grid">
					<div class="adbd-col adbd-map-col">
						<div id="adbd-map" class="adbd-map"></div>
					</div>
					<div class="adbd-col adbd-list-col">
						<div class="adbd-list">
							<div class="adbd-list-header">
								<input type="text" id="adbd-search" placeholder="<?php esc_attr_e( 'Search…', 'adbd' ); ?>" />
							</div>
							<div class="adbd-tree" id="adbd-tree"></div>
						</div>
					</div>
				</div>
			</div>
		</div><?php
	}

	/* -------------------------
	 * Field renderers
	 * ------------------------- */

	public static function field_client_key() {
		$settings = get_option( self::OPTION_NAME, [] );
		printf(
			'<input type="text" class="regular-text" name="%1$s[client_api_key]" value="%2$s" placeholder="Client key (Maps JS)" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['client_api_key'] ?? '' )
		);
		echo '<p class="description">' . esc_html__( 'Used to render the admin map (Maps JavaScript API). Restrict by HTTP referrer.', 'adbd' ) . '</p>';
	}

	public static function field_server_key() {
		$settings = get_option( self::OPTION_NAME, [] );
		printf(
			'<input type="text" class="regular-text" name="%1$s[server_api_key]" value="%2$s" placeholder="Server key (Directions/Distance)" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['server_api_key'] ?? '' )
		);
		echo '<p class="description">' . esc_html__( 'Reserved for future ETAs/routing (Directions & Distance Matrix). Restrict by server IP. Not used in this milestone.', 'adbd' ) . '</p>';
	}

	public static function field_origin_json() {
		$settings = get_option( self::OPTION_NAME, [] );
		printf(
			'<textarea class="large-text code" rows="3" name="%1$s[origin_json]">%2$s</textarea>',
			esc_attr( self::OPTION_NAME ),
			esc_textarea( $settings['origin_json'] ?? '' )
		);
		echo '<p class="description">' .
			esc_html__( 'Example:', 'adbd' ) .
			' <code>[{"id":"default","lat":34.097,"lng":-117.648,"mode":"driving"}]</code></p>';
	}

	public static function field_radius() {
		$settings = get_option( self::OPTION_NAME, [] );
		printf(
			'<input type="number" step="0.1" min="1" class="small-text" name="%1$s[radius_miles]" value="%2$s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['radius_miles'] ?? 30 )
		);
		echo '<span class="description"> ' . esc_html__( 'miles', 'adbd' ) . '</span>';
	}

	public static function field_statuses() {
		$settings  = get_option( self::OPTION_NAME, [] );
		$selected  = isset( $settings['order_statuses'] ) && is_array( $settings['order_statuses'] ) ? $settings['order_statuses'] : [ 'processing' ];
		$statuses  = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [ 'wc-processing' => 'Processing' ];

		echo '<fieldset>';
		foreach ( $statuses as $key => $label ) {
			$slug = ltrim( $key, 'wc-' );
			printf(
				'<label style="display:block;margin:2px 0;">
					<input type="checkbox" name="%1$s[order_statuses][]" value="%2$s" %3$s />
					%4$s
				</label>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $slug ),
				checked( in_array( $slug, $selected, true ), true, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'Only orders with these statuses will be shown and pinned.', 'adbd' ) . '</p>';
		echo '</fieldset>';
	}

	public static function field_status_colors() {
		$settings = get_option( self::OPTION_NAME, [] );
		$selected = isset( $settings['order_statuses'] ) && is_array( $settings['order_statuses'] ) ? $settings['order_statuses'] : [ 'processing' ];
		$colors   = isset( $settings['status_colors'] ) && is_array( $settings['status_colors'] ) ? $settings['status_colors'] : [];

		if ( empty( $selected ) ) {
			echo '<p class="description">' . esc_html__( 'Select at least one order status above to configure marker colors.', 'adbd' ) . '</p>';
			return;
		}

		echo '<fieldset>';
		foreach ( $selected as $slug ) {
			$val = $colors[ $slug ] ?? '';
			printf(
				'<label style="display:flex;align-items:center;gap:10px;margin:4px 0;">
					<span style="width:160px">%1$s</span>
					<input type="color" name="%2$s[status_colors][%3$s]" value="%4$s" />
				</label>',
				esc_html( ucfirst( str_replace( '-', ' ', $slug ) ) ),
				esc_attr( self::OPTION_NAME ),
				esc_attr( $slug ),
				esc_attr( $val )
			);
		}
		echo '<p class="description">' . esc_html__( 'Pick a pin color for each selected status. The map will use these colors for markers.', 'adbd' ) . '</p>';
		echo '</fieldset>';
	}
}
