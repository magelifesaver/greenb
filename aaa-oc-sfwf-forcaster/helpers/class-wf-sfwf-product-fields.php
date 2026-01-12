<?php
/**
 * Filepath: sfwf/helpers/class-wf-sfwf-product-fields.php
 * ---------------------------------------------------------------------------
 * Adds forecast fields to WooCommerce product edit screen and saves them.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Product_Fields {

	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
	}

	public static function add_fields() {
		echo '<div class="options_group">';
		woocommerce_wp_text_input( array(
			'id' => 'forecast_lead_time_days','label' => 'Forecast Lead Time (days)','type' => 'number',
			'description' => 'Overrides global lead time.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_minimum_order_qty','label' => 'Minimum Order Quantity','type' => 'number',
			'description' => 'Minimum units per purchase.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_tier_threshold_1','label' => 'Tier Threshold 1','type' => 'number',
			'description' => 'Days before reorder is Tier 1.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_tier_threshold_2','label' => 'Tier Threshold 2','type' => 'number',
			'description' => 'Days before reorder is Tier 2.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_tier_threshold_3','label' => 'Tier Threshold 3','type' => 'number',
			'description' => 'Days before reorder is Tier 3.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_sales_window_days','label' => 'Sales Window (days)','type' => 'number',
			'description' => 'Override sales window for this product.'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'forecast_cost_override','label' => 'Cost Override ($)','type' => 'number','custom_attributes' => array( 'step' => '0.01' ),
			'description' => 'Override cost for this product.'
		) );
		woocommerce_wp_select( array(
			'id' => 'forecast_product_class','label' => 'Product Class',
			'options' => array( '' => 'Default', 't1' => 'Tier 1', 't2' => 'Tier 2', 't3' => 'Tier 3' ),
			'description' => 'Override default class.'
		) );
		woocommerce_wp_checkbox( array( 'id' => 'forecast_enable_reorder','label' => 'Enable Reorder Forecasting' ) );
		woocommerce_wp_checkbox( array( 'id' => 'forecast_do_not_reorder','label' => 'Do Not Reorder (Manual Override)' ) );
		woocommerce_wp_checkbox( array( 'id' => 'forecast_always_in_stock','label' => 'Always In Stock (Never Flag OOS)' ) );
		woocommerce_wp_checkbox( array( 'id' => 'forecast_force_reorder','label' => 'Force Reorder (Always Flag)' ) );
		woocommerce_wp_textarea_input( array(
			'id' => 'forecast_reorder_note','label' => 'Reorder Note','description' => 'Internal note about reorder.'
		) );
		echo '</div>';
	}

	public static function save_fields( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) return;

		$checkboxes = array(
			'forecast_enable_reorder','forecast_do_not_reorder','forecast_always_in_stock','forecast_force_reorder'
		);
		foreach ( $checkboxes as $key ) {
			$val = isset($_POST[$key]) ? 'yes' : 'no';
			update_post_meta( $post_id, $key, $val );
		}

		$ints = array(
			'forecast_lead_time_days','forecast_minimum_order_qty',
			'forecast_tier_threshold_1','forecast_tier_threshold_2','forecast_tier_threshold_3',
			'forecast_sales_window_days'
		);
		foreach ( $ints as $key ) {
			$raw = isset($_POST[$key]) ? wc_clean( wp_unslash($_POST[$key]) ) : '';
			if ( $raw === '' ) delete_post_meta( $post_id, $key );
			else update_post_meta( $post_id, $key, absint($raw) );
		}

		$cost = isset($_POST['forecast_cost_override']) ? wc_clean( wp_unslash($_POST['forecast_cost_override']) ) : '';
		if ( $cost === '' ) delete_post_meta( $post_id, 'forecast_cost_override' );
		else update_post_meta( $post_id, 'forecast_cost_override', (string) floatval($cost) );

		$class = isset($_POST['forecast_product_class']) ? sanitize_text_field( wp_unslash($_POST['forecast_product_class']) ) : '';
		if ( $class === '' ) delete_post_meta( $post_id, 'forecast_product_class' );
		else update_post_meta( $post_id, 'forecast_product_class', $class );

		$note = isset($_POST['forecast_reorder_note']) ? sanitize_textarea_field( wp_unslash($_POST['forecast_reorder_note']) ) : '';
		if ( $note === '' ) delete_post_meta( $post_id, 'forecast_reorder_note' );
		else update_post_meta( $post_id, 'forecast_reorder_note', $note );

		// Queue behavior: Enable Reorder => queued; Disable => removed.
		if ( class_exists('WF_SFWF_Forecast_Queue') ) {
			if ( isset($_POST['forecast_enable_reorder']) ) WF_SFWF_Forecast_Queue::enqueue( $post_id, 'enable_reorder' );
			else WF_SFWF_Forecast_Queue::dequeue( $post_id );
		}
	}
}

WF_SFWF_Product_Fields::init();
