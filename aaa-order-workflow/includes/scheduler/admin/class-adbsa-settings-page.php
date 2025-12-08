<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/admin/class-adbsa-settings-page.php
 * Purpose: Main settings page for Delivery Blocks Scheduler Advanced (adbsa).
 * Version: 1.2.1 (fixed debug constant placement)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local debug toggle — must be defined outside the class.
 */
if ( ! defined( 'ADBSA_DEBUG_THIS_FILE' ) ) {
	define( 'ADBSA_DEBUG_THIS_FILE', true );
}

class ADBSA_Settings_Page {

	const OPT_KEY = 'adbsa_options_sameday'; // default view tab

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 50 );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	public static function add_menu() {
		add_submenu_page(
			'aaa-oc-workflow-board',
			'Delivery Scheduler Date and Time Settings',
			'WF Delivery Scheduler Settings',
			'manage_woocommerce',
			'adbsa-settings-page',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Register the group so settings_fields() works (values are loaded/saved manually).
	 */
	public static function register_settings() {
		register_setting(
			'adbsa_settings_page_group',
			self::OPT_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_all' ],
				'default'           => [],
				'show_in_rest'      => false,
			]
		);
	}

	/** Sanitize before writing to DB */
public static function sanitize_all( $input ) {
	$out = is_array( $input ) ? $input : [];
	$tab = isset( $_POST['adbsa_active_tab'] )
		? sanitize_key( $_POST['adbsa_active_tab'] )
		: ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'sameday' );

	// Determine which custom key to use per tab
	$key_map = [
		'sameday'   => 'adbsa_options_sameday',
		'scheduled' => 'adbsa_options_scheduled',
		'asap'      => 'adbsa_options_asap',
	];
	$target_key = $key_map[ $tab ] ?? 'adbsa_options_sameday';

	// Extract the mode data for this tab only
	// Support both old nested and new flat POST keys
	if ( isset( $_POST["adbsa_options_{$tab}"] ) ) {
	    $mode_data = (array) $_POST["adbsa_options_{$tab}"];
	} elseif ( isset( $out[ $tab ] ) ) {
	    $mode_data = (array) $out[ $tab ];
	} else {
	    $mode_data = [];
	}

	// Normalize numeric and checkbox fields
	foreach ( $mode_data as $k => $v ) {
		if ( is_numeric( $v ) || $v === '0' ) {
			$mode_data[ $k ] = (int) $v;
		} elseif ( is_string( $v ) ) {
			$mode_data[ $k ] = sanitize_text_field( $v );
		}
	}

	// Save to custom table (aaa_oc_options)
	self::update_custom_option( $target_key, $mode_data );

	if ( ADBSA_DEBUG_THIS_FILE ) {
		error_log( "[ADBSA][save] Tab={$tab} saved to {$target_key}" );
	}

	// Return just this tab's data (prevents overwriting other tabs)
	return $mode_data;
}

	/** Fetch settings from custom table */
	private static function get_custom_option( $key, $default = [] ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_options';
		$val = $wpdb->get_var( $wpdb->prepare(
			"SELECT option_value FROM {$table} WHERE option_key = %s LIMIT 1",
			$key
		) );
		if ( $val === null ) return $default;
		$decoded = maybe_unserialize( $val );
		return is_array( $decoded ) ? $decoded : $default;
	}

	/** Update settings in custom table */
	private static function update_custom_option( $key, $value ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_options';
		$data = [
			'option_key'   => $key,
			'option_value' => maybe_serialize( $value ),
			'updated_at'   => current_time( 'mysql' ),
		];
		$wpdb->replace( $table, $data );

		if ( ADBSA_DEBUG_THIS_FILE ) {
			error_log( "[ADBSA] Saved options to {$table} key={$key}" );
		}
	}

	public static function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'sameday';
		$tabs = [
			'sameday'   => 'Same-Day',
			'scheduled' => 'Scheduled',
			'asap'      => 'ASAP',
		];

		// Load saved settings so tab files can access them
		$current_opts = self::get_custom_option( self::OPT_KEY, [] );

		if ( ADBSA_DEBUG_THIS_FILE ) {
			error_log( '[ADBSA] Loaded options from aaa_oc_options: ' . json_encode( array_keys( $current_opts ) ) );
		}

		// Include tab definition (adds sections/fields)
		$file = plugin_dir_path( __FILE__ ) . 'tabs/tab-' . $tab . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
		?>
		<div class="wrap">
			<h1>Delivery Scheduler Settings</h1>
			<?php settings_errors(); ?>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
					   href="<?php echo esc_url( add_query_arg( [ 'page' => 'adbsa-settings-page', 'tab' => $key ], admin_url( 'admin.php' ) ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'adbsa_settings_page_group' );
				do_settings_sections( 'adbsa_settings_page_' . $tab );

				// Hidden field ensures sanitize_all() knows which tab was saved
				echo '<input type="hidden" name="adbsa_active_tab" value="' . esc_attr( $tab ) . '">';

				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}
}

ADBSA_Settings_Page::init();
