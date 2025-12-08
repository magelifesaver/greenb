<?php
/**
 * Plugin Name: Group Attributes for WooCommerce
 * Author: Plugify
 * Plugin URI: https://woo.com/products/group-attributes/
 * Author URI: https://woocommerce.com/vendor/plugify/
 * Version: 1.0.4
 * Description: Easily categorize attributes of complex products with many different attributes and values using Group Attributes for WooCommerce plugin.
 * Developed By: Plugify Team
 * License: GPL-2.0+
 * Woo: 18734002633174:164e8c4e1a4ae2ff6e77eda538933962
 * Requires at least: 4.4
 * Tested up to: 6.*.*
 * WC requires at least: 3.0
 * WC tested up to: 9.*.*
 */
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 * if wooCommerce is not active this plugin will not work.
 **/
if (!is_multisite()) {
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		function my_admin_notice() {		
			deactivate_plugins(__FILE__);
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			?>
			<div id="message" class="error">
				<p>Group Attributes for WooCommerce requires <a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be installed and active!</p> 
			</div>
			<?php
		}
		add_action( 'admin_notices', 'my_admin_notice' );
	}
}


error_reporting(0);


if (is_admin()) {

	include ('Admin/plgfyga_main_admin.php');
	

} else {

	include ('Front/plgfyega_main_front.php');
}



add_action('wp_ajax_plgfyga_group_atributes_sorting', 'plgfyga_group_atributes_sorting');
add_action('wp_ajax_plgfyga_save_final_array_to_use', 'plgfyga_save_final_array_to_use');
add_action('wp_ajax_plgfyga_save_display_settings_for_tables', 'plgfyga_save_display_settings_for_tables');
add_action('wp_ajax_plgfyga_group_atributes_sorting_taxonomiessss', 'plgfyga_group_atributes_sorting_taxonomiessss');
add_action('wp_ajax_plgfyga_group_atributes_unload_one_by_one', 'plgfyga_group_atributes_unload_one_by_one');
register_activation_hook( __FILE__, 'plgfyga_plugin_activate_function' );

add_action( 'before_woocommerce_init', 'plugify_ga_hpos_compatibility');
function plugify_ga_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}


	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {			
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}

}


function plgfyga_group_atributes_unload_one_by_one () {


	if (isset($_REQUEST['unselected_group'])) {
		$unselected_group = filter_var($_REQUEST['unselected_group']);
	}

	if (isset($_REQUEST['remaining_selected_groups'])) {
		$remaining_selected_group = array_map( 'sanitize_text_field', wp_unslash($_REQUEST['remaining_selected_groups']));
	}


	$unselected_group_k_attributes = get_post_meta($unselected_group, 'selected_attributes', true);





	$selected_k_attributes = array();

	

	foreach ($remaining_selected_group as $key => $value) {
		$selected_attributes = get_post_meta($value, 'selected_attributes', true);
		$selected_k_attributes=  array_merge($selected_k_attributes, $selected_attributes);
	}
	
	$remaining_attributes = array_values(array_diff($unselected_group_k_attributes, $selected_k_attributes));
	echo ( json_encode ( $remaining_attributes ) );




	wp_die();
}


function plgfyga_group_atributes_sorting_taxonomiessss () {
	
	if (isset($_REQUEST['saved_taxonomy_id'])) {

		$taxonomy_name = 'attr_taxonomy';
		$term_id = filter_var($_REQUEST['saved_taxonomy_id']);
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'plgfyga_grp_attr',
			'tax_query' => array(
				array(
					'taxonomy' => 'attr_taxonomy',
					'field' => 'term_id',
					'terms' => $term_id,
				),
			),
		);

		$posts = get_posts( $args );
		$posts_array = array();
		$group_all_data = array();
		foreach ( $posts as $post ) {
			$posts_array[] = $post->ID;


			$selected_attributes = get_post_meta($post->ID, 'selected_attributes', true);
			$group_all_data=  array_merge($group_all_data, $selected_attributes);

		}

		$all_data = array();

		$all_data['groups_ids'] = $posts_array;
		$all_data['groups_attributes'] = $group_all_data;

		echo json_encode($all_data);
	} else {
		echo json_encode(array());
	}
	wp_die();
}

function plgfyga_plugin_activate_function () {

	$display_settings = get_option('plgfyga_save_display_settings_for_tables');
	if ('' == $display_settings) {
		$display_settings = array(


			'plgfyga_table_location'=>'beforecart',
			'plgfyga_table_style'=>'tophead',
			'plgfyga_top_head_bg_color'=>'#ededed',
			'plgfyga_top_head_txt_color'=>'#000000',
			'plgfyga_top_body_bg_color'=>'#f7f7f7',
			'plgfyga_top_body_text_color'=>'#00000',
			'plgfyga_accor_head_bg_color'=>'#ededed',
			'plgfyga_accor_head_txt_color'=>'#00000',
			'plgfyga_accor_body_bg_color'=>'#f7f7f7',
			'plgfyga_accor_body_txt_color'=>'#00000',
			'plgfyga_tabs_bg_color'=>'#ededed',
			'plgfyga_tabs_txt_color'=>'#000',
			'plgfyga_tab_table_body_color'=>'#ededed',
			'plgfyga_tabs_table_body_txt_color'=>'#00000',
			'plgfyga_top_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_accor_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_tab_table_body_color_odd'=>'#E6E6E6',
			'plgfyga_tabs_head_main_bg_color'=>'#E6E6E6',
			'plgfyga_tabs_bg_active'=>'#fefefe',
			'plgfyga_tabs_txt_active'=>'#00000',
			'plgfyga_view_attri'=>'View Attributes',
			'plgfyga_btn_bg_color'=>'#00000',
			'plgfyga_btn_txt_color'=>'#fefefe',

		);
		update_option('plgfyga_save_display_settings_for_tables', $display_settings);
	}
}

function plgfyga_save_display_settings_for_tables () {

	update_option('plgfyga_save_display_settings_for_tables', $_REQUEST);

	wp_die();
}
function plgfyga_save_final_array_to_use () {

	if (isset($_REQUEST['pro_id']) && isset($_REQUEST['final_array'])) {
		update_post_meta(filter_var($_REQUEST['pro_id']), 'custom_saved_groups', array_map( 'sanitize_text_field', wp_unslash($_REQUEST['final_array'])));
	}
	if (isset($_REQUEST['create_for_this_product'])) {
		if (isset($_REQUEST['pro_id'])) {
			update_post_meta(filter_var($_REQUEST['pro_id']), 'create_for_this_product', 'on');			
		}

	} else {
		if (isset($_REQUEST['pro_id'])) {
			update_post_meta(filter_var($_REQUEST['pro_id']), 'create_for_this_product', 'off');
		}
	}


	wp_die();
}

function plgfyga_group_atributes_sorting () {


	$group_all_data = array();
	if (isset($_REQUEST['saved_grp_id'])) {
		$saved_grp_idddd = array_map( 'sanitize_text_field', wp_unslash($_REQUEST['saved_grp_id']));
	}
	if (is_array($saved_grp_idddd)) {
		foreach ($saved_grp_idddd as $key => $value) {
			$selected_attributes = get_post_meta($value, 'selected_attributes', true);
			$group_all_data=  array_merge($group_all_data, $selected_attributes);
		}		
	}

	echo ( json_encode( $group_all_data ) );
	wp_die();
}

add_filter( 'plugin_action_links', 'plugify_group_attr_links11qty' , 10, 2 );
function plugify_group_attr_links11qty( $links, $file ) {
	
	if ( 'grouped-attributes-for-woocommerce/grouped-attributes-for-woocommerce.php' == $file ) {
		
		$settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=plgfyga' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>';

		array_push( $links, $settings);

	}


	return (array) $links;
}
