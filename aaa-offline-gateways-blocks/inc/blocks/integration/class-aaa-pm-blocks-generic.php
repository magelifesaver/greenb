<?php
/**
 * Generic Blocks integration (per gateway ID)
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/inc/blocks/integration/class-aaa-pm-blocks-generic.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class AAA_PM_Blocks_Generic extends AbstractPaymentMethodType {
	protected $name;      // gateway id (e.g., pay_with_zelle)
	protected $settings;  // loaded WC options

	public function __construct( $gateway_id ) { $this->name = $gateway_id; }
	public function get_name() { return $this->name; }

	public function initialize() {
		$opt_key       = 'woocommerce_' . $this->name . '_settings';
		$this->settings = get_option( $opt_key, array() );

		// Log a compact snapshot (no PII).
		$enabled  = $this->settings['enabled'] ?? 'no';
		$backend  = $this->settings['backend_only'] ?? 'no';
		$tip_en   = $this->settings['enable_tipping'] ?? 'no';
		aaa_pm_log('[Blocks][' . $this->name . '] initialize: enabled=' . $enabled . ' backend_only=' . $backend . ' tip=' . $tip_en);
	}

	public function is_active() {
		$enabled      = $this->settings['enabled'] ?? 'no';
		$backend_only = $this->settings['backend_only'] ?? 'no';

		// In wp-admin (block editors), treat enabled gateways as "supported"
		// so the "Some active extensions do not yet support this block" notice
		// does not appear for backend-only methods.
		if ( is_admin() ) {
			$active = ( 'yes' === $enabled );
		} else {
			// Storefront: only active if enabled AND not backend-only.
			$active = ( 'yes' === $enabled ) && ( 'yes' !== $backend_only );
		}

		aaa_pm_log(
			'[Blocks][' . $this->name . '] is_active=' . ( $active ? 'yes' : 'no' ) .
			' (admin=' . ( is_admin() ? 'yes' : 'no' ) . ', backend_only=' . $backend_only . ')'
		);
		return $active;
	}

	public function get_payment_method_script_handles() {
		aaa_pm_log('[Blocks][' . $this->name . '] script handle requested');
		return array( 'aaa-pm-blocks' );
	}

	public function enqueue_payment_method_type_scripts() {
		aaa_pm_log('[Blocks][' . $this->name . '] enqueue script');
		wp_enqueue_script( 'aaa-pm-blocks' );
	}

	public function get_payment_method_data() {
		$title       = $this->settings['title'] ?? ucfirst( str_replace( array( 'pay_with_', '_' ), array( '', ' ' ), $this->name ) );
		$description = $this->settings['description'] ?? '';

		$data = array(
			'title'       => $title,
			'description' => $description,
			// Required so Checkout Blocks will consider it.
			'supports'    => array( 'products' ),
			'tipping'     => array(
				'enabled' => ( $this->settings['enable_tipping'] ?? 'no' ) === 'yes',
				'default' => $this->settings['tipping_default_amount'] ?? '',
				'presets' => $this->settings['tipping_presets'] ?? '',
			),
			'fields'      => array(
				'f1'       => ( $this->settings['enable_checkout_field_1'] ?? 'no' ) === 'yes',
				'f1_label' => $this->settings['checkout_field_1_label'] ?? '',
				'f1_ph'    => $this->settings['checkout_field_1_placeholder'] ?? '',
				'f2'       => ( $this->settings['enable_checkout_field_2'] ?? 'no' ) === 'yes',
				'f2_label' => $this->settings['checkout_field_2_label'] ?? '',
				'f2_ph'    => $this->settings['checkout_field_2_placeholder'] ?? '',
				'fa'       => ( $this->settings['enable_checkout_text_area'] ?? 'no' ) === 'yes',
				'fa_label' => $this->settings['checkout_text_area_label'] ?? '',
				'fa_ph'    => $this->settings['checkout_text_area_placeholder'] ?? '',
			),
		);

		aaa_pm_log('[Blocks][' . $this->name . '] data exposed to JS');
		return $data;
	}
}
