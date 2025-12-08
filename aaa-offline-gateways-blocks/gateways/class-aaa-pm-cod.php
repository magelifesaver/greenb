<?php
/**
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/gateways/class-aaa-pm-cod.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'AAA_PM_Gateway_Base' ) ) {
	require_once __DIR__ . '/base/class-aaa-pm-base-gateway.php';
}
class AAA_PM_Gateway_COD extends AAA_PM_Gateway_Base {
	public function __construct() {
		// Controlled COD; distinct id from core 'cod'.
		parent::__construct( 'pay_with_cod', __( 'Cash on Delivery', 'aaa-offline-gateways-blocks' ), __( 'Cash on Delivery', 'aaa-offline-gateways-blocks' ), __( 'Custom COD Method', 'aaa-offline-gateways-blocks' ) );
	}
}
