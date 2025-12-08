<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	require_once dirname( __FILE__ ) . '/class-woogc-report-stock.php';
}

/**
 * WC_Report_Out_Of_Stock.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WooGC_Report_Out_Of_Stock extends WC_Report_Stock {

	/**
	 * No items found text.
	 */
	public function no_items() {
		_e( 'No out of stock products found.', 'woocommerce' );
	}

	/**
	 * Get Products matching stock criteria.
	 *
	 * @param int $current_page
	 * @param int $per_page
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb, $WooGC, $blog_id;;

		$this->max_items = 0;
		$this->items     = array();

		// Get products using a query - this is too advanced for get_posts :(
		$stock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );
       
        
        $network_sites  =   $WooGC->functions->get_gc_sites( TRUE, 'global_reports' );
        $query  =   array();
        
        $select_fields  =   '*';
        
        foreach ( $network_sites as $site )
            { 
                switch_to_blog( $site->blog_id );
                $query_from = "FROM {$wpdb->posts} as posts
                                    INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
                                    INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
                                    WHERE 1=1
                                    AND posts.post_type IN ( 'product', 'product_variation' )
                                    AND posts.post_status = 'publish'
                                    AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
                                    AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}'
                                ";
                
                $query[]   =   "SELECT posts.ID as id, posts.post_parent as parent, posts.post_title as post_title, ".  $blog_id ." as blog_id {$query_from} GROUP BY posts.ID" ;
                restore_current_blog();
            }

        $query_order    =   $wpdb->prepare( "ORDER BY post_title ASC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page );
        $main_query =   "SELECT  * FROM ( " . implode( " UNION ALL ", $query ) . ") as q ". $query_order ;
        
        $this->items     = $wpdb->get_results( $main_query );
        $this->max_items = $wpdb->get_var( "SELECT COUNT( id ) FROM ( " . implode( " UNION ALL ", $query ) . ") as q " );
	}
}
