<?php
/**
 * Plugin Name: AAA FluentCRM Opt-in (Checkout Blocks)
 * Description: Adds a marketing opt-in checkbox to WooCommerce Checkout Blocks and syncs consent to FluentCRM (lists/tags). Classic checkout remains handled by FluentCRM’s own setting.
 * Version: 1.1.0
 * Author: AAA Workflow
 *
 * File Path: wp-content/plugins/aaa-fluentcrm-optin-block/aaa-fluentcrm-optin-block.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_FLCRM_Optin_Block' ) ) :

final class AAA_FLCRM_Optin_Block {

	const OPTS_KEY   = 'flcrm_co_settings';
	const FIELD_ID   = 'flcrm/marketing-optin'; // Saved under _wc_other/flcrm/marketing-optin
	const LOG_SOURCE = 'flcrm-checkout-optin';

	private $field_registered = false;

	public static function instance() {
		static $inst = null;
		if ( null === $inst ) { $inst = new self(); }
		return $inst;
	}

	private function __construct() {
		// Admin settings
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Register the Additional Checkout Field for Blocks (run after Woo is ready / Blocks loaded)
		add_action( 'woocommerce_init',       [ $this, 'register_optin_field' ], 20 );
		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_optin_field' ], 20 );

		// Process ONLY Blocks checkout orders (Store API). Classic checkout remains handled by FluentCRM core feature.
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'handle_storeapi_checkout' ], 20, 1 );

		// Notices
		add_action( 'admin_notices', [ $this, 'maybe_admin_notice' ] );
	}

	/* =========================
	 * Admin settings
	 * =======================*/
	public function register_settings_page() {
		add_options_page(
			'FluentCRM Checkout Opt-in',
			'FluentCRM Checkout Opt-in',
			'manage_options',
			'flcrm-co',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'flcrm_co_group', self::OPTS_KEY, [ $this, 'sanitize_opts' ] );
		add_settings_section( 'flcrm_co_main', 'Settings', '__return_false', 'flcrm-co' );
		add_settings_field( 'label',   'Checkbox label',        [ $this, 'field_label'   ], 'flcrm-co', 'flcrm_co_main' );
		add_settings_field( 'lists',   'Lists to attach',       [ $this, 'field_lists'   ], 'flcrm-co', 'flcrm_co_main' );
		add_settings_field( 'tags',    'Tags to attach',        [ $this, 'field_tags'    ], 'flcrm-co', 'flcrm_co_main' );
		add_settings_field( 'status',  'Subscription status',   [ $this, 'field_status'  ], 'flcrm-co', 'flcrm_co_main' );
		add_settings_field( 'logging', 'Enable logging',        [ $this, 'field_logging' ], 'flcrm-co', 'flcrm_co_main' );
	}

	public function default_opts() {
		return [
			'label'   => 'Email me product updates and offers',
			'lists'   => '',
			'tags'    => 'wc-checkout-optin',
			'status'  => 'subscribed', // or 'pending' (to trigger DOI), 'unsubscribed'
			'logging' => 'yes',
		];
	}

	public function get_opts() {
		return wp_parse_args( get_option( self::OPTS_KEY, [] ), $this->default_opts() );
	}

	public function sanitize_opts( $opts ) {
		$def = $this->default_opts();
		$out = [];
		$out['label']   = isset( $opts['label'] ) ? sanitize_text_field( $opts['label'] ) : $def['label'];
		$out['lists']   = isset( $opts['lists'] ) ? sanitize_text_field( $opts['lists'] ) : '';
		$out['tags']    = isset( $opts['tags'] )  ? sanitize_text_field( $opts['tags'] )  : '';
		$out['status']  = in_array( $opts['status'] ?? '', [ 'subscribed', 'pending', 'unsubscribed' ], true ) ? $opts['status'] : 'subscribed';
		$out['logging'] = ( ! empty( $opts['logging'] ) && $opts['logging'] === 'yes' ) ? 'yes' : 'no';
		return $out;
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>FluentCRM Checkout Opt-in (Blocks)</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'flcrm_co_group' ); ?>
				<?php do_settings_sections( 'flcrm-co' ); ?>
				<?php submit_button(); ?>
			</form>
			<p><em>Notes:</em> This add-on is <strong>Blocks-only</strong>. Classic checkout is handled by FluentCRM’s built-in checkout opt-in field. Lists are optional; tags recommended.</p>
		</div>
		<?php
	}

	public function field_label()  { $v = esc_attr( $this->get_opts()['label'] );  echo '<input type="text" class="regular-text" name="'.esc_attr(self::OPTS_KEY).'[label]" value="'.$v.'">'; }
	public function field_lists()  { $v = esc_attr( $this->get_opts()['lists'] );  echo '<input type="text" class="regular-text" placeholder="e.g. Customers, 3, vip-list" name="'.esc_attr(self::OPTS_KEY).'[lists]" value="'.$v.'">'; }
	public function field_tags()   { $v = esc_attr( $this->get_opts()['tags'] );   echo '<input type="text" class="regular-text" placeholder="e.g. wc-checkout-optin, newsletter" name="'.esc_attr(self::OPTS_KEY).'[tags]" value="'.$v.'">'; }
	public function field_status() {
		$v = esc_attr( $this->get_opts()['status'] ); ?>
		<select name="<?php echo esc_attr( self::OPTS_KEY ); ?>[status]">
			<option value="subscribed"   <?php selected( $v, 'subscribed' ); ?>>Subscribed (no DOI)</option>
			<option value="pending"      <?php selected( $v, 'pending' );    ?>>Pending (send Double Opt-in)</option>
			<option value="unsubscribed" <?php selected( $v, 'unsubscribed'); ?>>Unsubscribed</option>
		</select>
		<?php
	}
	public function field_logging() { $v = $this->get_opts()['logging']; echo '<label><input type="checkbox" name="'.esc_attr(self::OPTS_KEY).'[logging]" value="yes" '.checked($v,'yes',false).'> Log actions to Woo logs</label>'; }

	/* =========================
	 * Register field (Blocks)
	 * =======================*/
	public function register_optin_field() {
		if ( $this->field_registered ) { return; }
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log( 'Blocks Additional Checkout Fields API not found; field not registered.' );
			return;
		}

		$label    = $this->get_opts()['label'];
		$location = 'contact'; // or 'order' to move under Order information

		try {
			woocommerce_register_additional_checkout_field( [
				'id'            => self::FIELD_ID,
				'label'         => $label,
				'optionalLabel' => $label, // avoid the "(optional)" suffix
				'location'      => $location,
				'type'          => 'checkbox',
				'required'      => false,
				'default'       => false,
			] );
			$this->field_registered = true;
			$this->log( sprintf( 'Registered Blocks field %s at %s', self::FIELD_ID, $location ) );
		} catch ( \Throwable $e ) {
			$this->log( 'Failed to register field: '.$e->getMessage() );
		}
	}

	/* =========================
	 * Checkout processing (Blocks)
	 * =======================*/
	public function handle_storeapi_checkout( $order ) {
		if ( ! $order instanceof WC_Order ) {
			$this->log( 'StoreAPI hook received invalid order.' );
			return;
		}
		$this->maybe_subscribe_from_order( $order );
	}

	private function maybe_subscribe_from_order( WC_Order $order ) {
		$optin = $this->read_optin_from_order( $order );
		$this->log( sprintf( 'Order %d opt-in value: %s', $order->get_id(), var_export( $optin, true ) ) );

		$label = $this->get_opts()['label'];
		$now   = current_time( 'mysql' );
		$ip    = class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '';

		// Store proof on the order (useful for audits)
		$order->update_meta_data( '_flcrm_optin_label',   $label );
		$order->update_meta_data( '_flcrm_optin_time',    $now );
		$order->update_meta_data( '_flcrm_optin_ip',      $ip );
		$order->update_meta_data( '_flcrm_optin_checked', $optin ? 'yes' : 'no' );
		$order->save();

		if ( ! $optin ) { return; } // no consent

		if ( ! function_exists( 'FluentCrmApi' ) ) {
			$this->log( 'FluentCRM not detected; skipping contact sync.' );
			return;
		}

		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			$this->log( 'Invalid/missing email; skipping contact sync.' );
			return;
		}

		$opts   = $this->get_opts();
		$lists  = $this->csv_to_array( $opts['lists'] );
		$tags   = $this->csv_to_array( $opts['tags'] );
		$status = $opts['status'];

		$data = [
			'email'        => $email,
			'first_name'   => $order->get_billing_first_name(),
			'last_name'    => $order->get_billing_last_name(),
			'status'       => $status,
			'contact_type' => 'customer',
			'source'       => 'woo_checkout',
			'ip'           => $ip,
			'lists'        => $lists,
			'tags'         => $tags,
			'custom_values'=> [
				'checkout_optin_time'  => $now,
				'checkout_optin_order' => (string) $order->get_order_number(),
				'checkout_optin_text'  => $label,
			],
		];

		try {
			$contact = FluentCrmApi( 'contacts' )->createOrUpdate( $data, true );
			if ( $contact && $contact->status === 'pending' && method_exists( $contact, 'sendDoubleOptinEmail' ) ) {
				$contact->sendDoubleOptinEmail();
			}
			$order->add_order_note( 'Customer opted into marketing (FluentCRM synced).' );
			$this->log( sprintf( 'FluentCRM createOrUpdate OK for %s (subscriber_id: %s)', $email, $contact ? $contact->id : 'n/a' ) );
		} catch ( \Throwable $e ) {
			$this->log( 'FluentCRM error: '.$e->getMessage() );
		}
	}

	private function read_optin_from_order( WC_Order $order ) : bool {
		// Preferred: Blocks helper (stable across migrations)
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) && class_exists( '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
			try {
				$checkout_fields = \Automattic\WooCommerce\Blocks\Package::container()
					->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
				$other = $checkout_fields->get_all_fields_from_object( $order, 'other', true ); // include fields that might be unregistered
				if ( isset( $other[ self::FIELD_ID ] ) ) {
					$v = $other[ self::FIELD_ID ];
					return $v === '1' || $v === 1 || $v === true;
				}
			} catch ( \Throwable $e ) {
				// fall back to direct meta
			}
		}
		// Fallback: direct meta key for contact/order group (_wc_other/)
		$raw = $order->get_meta( '_wc_other/' . self::FIELD_ID );
		return $raw === '1' || $raw === 1 || $raw === true;
	}

	private function csv_to_array( string $csv ) : array {
		if ( ! $csv ) return [];
		$parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
		return array_map( function( $v ) { return is_numeric( $v ) ? (int) $v : $v; }, $parts );
	}

	private function log( $msg ) {
		$opts = $this->get_opts();
		if ( 'yes' !== $opts['logging'] ) return;
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->info( $msg, [ 'source' => self::LOG_SOURCE ] );
		} else {
			error_log( "[flcrm-co] $msg" );
		}
	}

	public function maybe_admin_notice() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-error"><p><strong>FluentCRM Checkout Opt-in:</strong> WooCommerce must be active.</p></div>';
		}
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			echo '<div class="notice notice-warning"><p><strong>FluentCRM Checkout Opt-in:</strong> FluentCRM not detected — contacts will not be synced until it’s active.</p></div>';
		}
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			echo '<div class="notice notice-info"><p><strong>FluentCRM Checkout Opt-in:</strong> Your WooCommerce/Blocks version may be old; the <em>Additional Checkout Fields</em> API is required for the block checkout checkbox.</p></div>';
		}
	}
}

AAA_FLCRM_Optin_Block::instance();

endif;
