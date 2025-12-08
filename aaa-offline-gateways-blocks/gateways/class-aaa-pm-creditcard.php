<?php
/**
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/gateways/class-aaa-pm-creditcard.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'AAA_PM_Gateway_Base' ) ) {
	require_once __DIR__ . '/base/class-aaa-pm-base-gateway.php';
}
class AAA_PM_Gateway_CreditCard extends AAA_PM_Gateway_Base {
	public function __construct() {
		parent::__construct( 'pay_with_creditcard', __( 'Credit Card', 'aaa-offline-gateways-blocks' ), __( 'Credit Card', 'aaa-offline-gateways-blocks' ), __( 'Custom Credit Card Payment Method', 'aaa-offline-gateways-blocks' ) );
	}
}
