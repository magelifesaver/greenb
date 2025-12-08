<?php

    defined( 'ABSPATH' ) || exit;


    /**
     * WC_Admin_Reports Class.
     */
    class WooGC_Network_Admin_Reports {

	    /**
	     * Handles output of the reports page in admin.
	     */
	    public static function output() {
		    $reports        = self::get_reports();
		    $first_tab      = array_keys( $reports );
		    $current_tab    = ! empty( $_GET['tab'] ) ? sanitize_title( $_GET['tab'] ) : $first_tab[0];
		    $current_report = isset( $_GET['report'] ) ? sanitize_title( $_GET['report'] ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

            global $WooGC;
            
            if ( $WooGC->functions->is_HPOS_active() )
                {
                    include_once dirname( __FILE__ ) . '/reports-hpos/class-woogc-admin-report.php';
                }
                else
                {
		            include_once dirname( __FILE__ ) . '/reports/class-woogc-admin-report.php';
                }
                
            include_once dirname( __FILE__ ) . '/views/html-admin-page-reports.php';
	    }

	    /**
	     * Returns the definitions for the reports to show in admin.
	     *
	     * @return array
	     */
	    public static function get_reports() {
		    $reports = array(
			    'orders'    => array(
				    'title'   => __( 'Orders', 'woocommerce' ),
				    'reports' => array(
					    'sales_by_date'     => array(
						    'title'       => __( 'Sales by date', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
					    'sales_by_product'  => array(
						    'title'       => __( 'Sales by product', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
	
				    ),
			    ),
			    'customers' => array(
				    'title'   => __( 'Customers', 'woocommerce' ),
				    'reports' => array(
					    'customers'     => array(
						    'title'       => __( 'Customers vs. guests', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
					    'customer_list' => array(
						    'title'       => __( 'Customer list', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
				    ),
			    ),
			    'stock'     => array(
				    'title'   => __( 'Stock', 'woocommerce' ),
				    'reports' => array(
					    'low_in_stock' => array(
						    'title'       => __( 'Low in stock', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
					    'out_of_stock' => array(
						    'title'       => __( 'Out of stock', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
					    'most_stocked' => array(
						    'title'       => __( 'Most stocked', 'woocommerce' ),
						    'description' => '',
						    'hide_title'  => true,
						    'callback'    => array( __CLASS__, 'get_report' ),
					    ),
				    ),
			    ),
		    );

	
		    $reports = apply_filters( 'woocommerce_admin_reports', $reports );
		    $reports = apply_filters( 'woocommerce_reports_charts', $reports ); // Backwards compatibility.

		    foreach ( $reports as $key => $report_group ) {
			    if ( isset( $reports[ $key ]['charts'] ) ) {
				    $reports[ $key ]['reports'] = $reports[ $key ]['charts'];
			    }

			    foreach ( $reports[ $key ]['reports'] as $report_key => $report ) {
				    if ( isset( $reports[ $key ]['reports'][ $report_key ]['function'] ) ) {
					    $reports[ $key ]['reports'][ $report_key ]['callback'] = $reports[ $key ]['reports'][ $report_key ]['function'];
				    }
			    }
		    }

		    return $reports;
	    }

	    /**
	     * Get a report from our reports subfolder.
	     *
	     * @param string $name
	     */
	    public static function get_report( $name ) {
		    $name  = sanitize_title( str_replace( '_', '-', $name ) );
		    $class = 'WooGC_Report_' . str_replace( '-', '_', $name );

            global $WooGC;
            
            $sufix =   '';
            if ( $WooGC->functions->is_HPOS_active() )
                $sufix =   '-hpos';
            
		    include_once apply_filters( 'wc_admin_reports_path', 'reports' . $sufix . '/class-woogc-report-' . $name . '.php', $name, $class );

		    if ( ! class_exists( $class ) ) {
			    return;
		    }

		    $report = new $class();
		    $report->output_report();
	    }
    }
