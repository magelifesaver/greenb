<?php
/**
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/gateways/class-aaa-pm-applepay.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'AAA_PM_Gateway_Base' ) ) {
	require_once __DIR__ . '/base/class-aaa-pm-base-gateway.php';
}
class AAA_PM_Gateway_ApplePay extends AAA_PM_Gateway_Base {
	public function __construct() {
		parent::__construct( 'pay_with_applepay', __( 'Pay With ApplePay', 'aaa-offline-gateways-blocks' ), __( 'Pay With ApplePay', 'aaa-offline-gateways-blocks' ), __( 'Custom ApplePay Payment Method', 'aaa-offline-gateways-blocks' ) );
	}
}
