<?php
/**
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/gateways/class-aaa-pm-zelle.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'AAA_PM_Gateway_Base' ) ) {
	require_once __DIR__ . '/base/class-aaa-pm-base-gateway.php';
}

class AAA_PM_Gateway_Zelle extends AAA_PM_Gateway_Base {
	public function __construct() {
		parent::__construct(
			'pay_with_zelle',
			__( 'Zelle', 'aaa-offline-gateways-blocks' ),
			__( 'Zelle', 'aaa-offline-gateways-blocks' ),
			__( 'Custom Zelle Payment Method', 'aaa-offline-gateways-blocks' )
		);
	}

	/** Make Zelle collect a reference by default. */
	public function init_form_fields() {
		parent::init_form_fields();
		if ( isset( $this->form_fields['require_reference'] ) ) {
			$this->form_fields['require_reference']['default'] = 'yes';
		}
		if ( isset( $this->form_fields['reference_label'] ) ) {
			$this->form_fields['reference_label']['default'] = __( 'Zelle memo or transaction ID', 'aaa-offline-gateways-blocks' );
		}
		if ( isset( $this->form_fields['reference_placeholder'] ) ) {
			$this->form_fields['reference_placeholder']['default'] = __( 'e.g., Order 12345 (memo) or Zelle confirmation ID', 'aaa-offline-gateways-blocks' );
		}
	}
}
