<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Account_Funds_Reports
 */
class WC_Account_Funds_Reports {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_reports_charts', [ $this, 'reports_charts' ] );
	}

	/**
	 * Add charts to WC
	 */
	public function reports_charts( $charts ) {
		$charts['deposits'] = [
			/* translators: Account funds deposits */
			'title'  => __( 'Deposits', 'woocommerce-account-funds' ),
			'charts' => [
				'deposits_by_date' => [
					/* translators: Account funds deposits overview */
					'title'       => __( 'Overview', 'woocommerce-account-funds' ),
					'description' => '',
					'hide_title'  => true,
					'function'    => [ $this, 'get_report' ],
				],
			],
		];
		return $charts;
	}

	/**
	 * Get the report
	 */
	public function get_report() {
		include_once WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php';
		include_once 'class-wc-account-funds-deposits-by-date.php';

		$report = new WC_Report_Deposits_By_Date();
		$report->output_report();
	}
}

new WC_Account_Funds_Reports();
