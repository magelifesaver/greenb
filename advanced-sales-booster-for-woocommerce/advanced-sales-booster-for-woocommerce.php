<?php
/**
 * Plugin Name: Advanced Sales Booster For Woocommerce
 * Plugin URI: https://woocommerce.com/products/advanced-sales-booster/
 * Author: Plugify
 * Author URI: https://woocommerce.com/vendor/plugify/
 * Version: 2.0.0
 * Description: Supercharge your store with the 6-in-1 Sales Booster extension. It includes AI Bought Together, Product Discounts, Upsell, and many more.
 * Developed By: Plugify
 * Requires at least: 4.4
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to: 6.*.*
 * Requires PHP: 7.4
 * Text Domain: frg
 * WC requires at least: 3.0
 * WC tested up to: 10.*.*
 * Woo: 18734001531158:43fbdebcc17e11fdb70e8ac9cf49ccd6

 */
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 * if wooCommerce is not active this plugin will not work.
 **/
if ( !is_multisite() ) {
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		function absb_admin_notice() {
			deactivate_plugins(__FILE__);
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			?>
			<div id="message" class="error">
				<p>Advanced Sales Booster For Woocommerce requires <a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be installed and active!</p> 
			</div>
			<?php
		}
		add_action( 'admin_notices', 'absb_admin_notice' );
	}
}

error_reporting(0);
use Automattic\WooCommerce\Utilities\OrderUtil;
add_action( 'before_woocommerce_init', 'plugify_asb_hpos_compatibility' );
function plugify_asb_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}

if ( is_admin() ) {
	include 'admin/absb_main_admin.php';
	register_activation_hook( __FILE__, 'absb_qty_discount_plugin_activate_function' );
	add_action( 'wp_ajax_absb_saving_first_rule_settings', 'absb_saving_first_rule_settings' );
	add_action( 'wp_ajax_absb_get_all_rules_from_db', 'absb_data_to_datatable' );
	add_action( 'wp_ajax_absb_deleting_rule', 'absb_delete_rule_functionss' );
	add_action( 'wp_ajax_absb_popup_for_edit', 'absb_edit_rules_div_function' );
	add_action( 'wp_ajax_absb_update_edited_rules_settings', 'absb_update_edited_rules_settings_function' );
	add_action( 'wp_ajax_absb_save_general_settings_quantity_discount', 'absb_save_general_settings_quantity_discount' );


	add_action( 'wp_ajax_saving_product_frequently_bought', 'absb_frg_bgt_saving_product_frequently_bought' );
	add_action( 'wp_ajax_save_first_time_frq_bgt', 'save_first_time_frq_bgt' );
	add_action( 'wp_ajax_absb_get_all_rules_from_db_for_frq_bgt', 'absb_get_all_rules_from_db_for_frq_bgt' );
	add_action( 'wp_ajax_absb_deleting_rule_frq_bgt', 'absb_deleting_rule_frq_bgt' );
	add_action( 'wp_ajax_absb_edit_frq_bgt', 'absb_edit_frq_bgt' );
	add_action( 'wp_ajax_absb_update_edited_rules_settings_frq_bgt', 'absb_update_edited_rules_settings_frq_bgt' );
	add_action( 'wp_ajax_frq_bgt_search_productss', 'absb_frq_bgt_search_productss' );
	add_action( 'wp_ajax_frq_bgt_search_productss_duplicate', 'absb_grggggg_frq_bgt_search_productss_duplicate' );
	add_action( 'wp_ajax_frq_bgt_search_productss_for_price_negotiate', 'frq_bgt_search_productss_for_price_negotiate' );
	add_action( 'wp_ajax_frq_bgt_search_productss_only_for_frq_bgt', 'frq_bgt_search_productss_only_for_frq_bgt' );
	add_action( 'wp_ajax_frq_bgt_search_productss_for_instock_only', 'frq_bgt_search_productss_for_instock_only' );


	add_action( 'wp_ajax_frq_bgt_saving_general_settings', 'frq_bgt_saving_general_settings' );
	add_action( 'wp_ajax_frq_bgt_prevent_duplicate_value', 'frq_bgt_prevent_duplicate_value' );


	add_action( 'wp_ajax_nfs_saving_general_settings_for_notify', 'nfs_saving_general_settings_for_notify' );
	add_action( 'wp_ajax_nfs_saving_display_settings_for_notify', 'nfs_saving_display_settings_for_notify' );
	add_action( 'wp_ajax_nfs_saving_time_settings_for_notify', 'nfs_saving_time_settings_for_notify' );
	add_action( 'wp_ajax_nfs_saving_message_settings_for_notify', 'nfs_saving_message_settings_for_notify' );
	add_action( 'wp_ajax_nfs_saving_shortcode_settings_for_notify', 'nfs_saving_shortcode_settings_for_notify' );
	add_action( 'wp_ajax_nfs_ajax_for_notification_show', 'nfs_ajax_for_notification_show' );
	add_action( 'wp_ajax_nopriv_nfs_ajax_for_notification_show', 'nfs_ajax_for_notification_show' );


	add_action( 'wp_ajax_saving_mail_template_setting', 'nfs_absbs_saving_mail_template_setting' );
	add_action( 'wp_ajax_absb_sending_mail', 'absb_sending_mail' );
	add_action( 'wp_ajax_absb_sending_mail_to_all', 'absb_sending_mail_to_all' );
	add_action( 'wp_ajax_ajax_for_creating_select_for_template', 'absb_sftmskal_ajax_for_creating_select_for_template' );
	add_action( 'wp_ajax_creating_wp_editor_for_mailing_option', 'creating_wp_editor_for_mailing_option' );


	add_action( 'wp_ajax_absb_upsell_funnel_save_general_settings', 'absb_upsell_funnel_save_general_settings' );
	add_action( 'wp_ajax_absb_saving_upsell_rule', 'absb_saving_upsell_rule' );
	add_action( 'wp_ajax_upsell_get_all_rules_from_db_fordatatable', 'absb_ups_upsell_get_all_rules_from_db_fordatatable' );
	add_action( 'wp_ajax_upsell_popup_for_edit', 'absb_ups_upsell_popup_for_edit' );
	add_action( 'wp_ajax_upsell_deleting_rule', 'absb_ups_upsell_deleting_rule' );
	add_action( 'wp_ajax_absb_update_upsell_rule', 'absb_update_upsell_rule' );


	add_action( 'wp_ajax_absb_saving_offers_general_settingss', 'absb_saving_offers_general_settingss' );
	add_action( 'wp_ajax_absb_saving_rule_for_offer', 'absb_saving_rule_for_offer' );
	add_action( 'wp_ajax_absbs_offer_get_all_rules_from_db_fordatatable', 'absbs_offer_get_all_rules_from_db_fordatatable' );
	add_action( 'wp_ajax_absb_offer_deleting_rule', 'absb_offer_deleting_rule' );
	add_action( 'wp_ajax_absb_offer_popup_for_edit', 'absb_offer_popup_for_edit' );
	add_action( 'wp_ajax_absb_updating_eidted_rule_for_offer', 'absb_updating_eidted_rule_for_offer' );
	add_action( 'wp_ajax_absb_saving_email_templates_for_offer_mod', 'absb_saving_email_templates_for_offer_mod' );
	add_action( 'wp_ajax_nopriv_absb_saving_email_templates_for_offer_mod', 'absb_saving_email_templates_for_offer_mod' );
	add_action( 'wp_ajax_absb_sending_price_request_to_db', 'absb_sending_price_request_to_db' );
	add_action( 'wp_ajax_nopriv_absb_sending_price_request_to_db', 'absb_sending_price_request_to_db' );
	add_action( 'init', 'absb_delete_rule_data_db1' );


	add_action( 'add_meta_boxes', 'absb_creating_meta_box' );
	add_action( 'save_post', 'absb_save_post_daata' );

	add_action( 'wp_ajax_absb_view_offer_popup', 'absb_view_offer_popup' );
	add_action( 'wp_ajax_nopriv_absb_view_offer_popup', 'absb_view_offer_popup' );
	add_action( 'wp_ajax_absb_view_request_record_on_my_account_page', 'absb_view_request_record_on_my_account_page' );
	add_action( 'wp_ajax_nopriv_absb_view_request_record_on_my_account_page', 'absb_view_request_record_on_my_account_page' );

 
} else {

	$gen_setting_for_freq_bgt_products = get_option( 'frq_bgt_general_settings' );
	$gen_setting_for_quantity_Discnt = get_option( 'absb_gen_settings_for_quantity_discount' );
	$gen_setting_for_sales_notification = get_option( 'nfs_general_settings_for_notify' );
	$gen_setting_for_upsell_products = get_option( 'absb_saved_upsell_general_settings' );
	$gen_setting_for_price_negotiation = get_option( 'absb_saved_general_settings_for_price_negotiate' );

	if (!isset($gen_setting_for_quantity_Discnt['qty_dsct_activate'])) {
		$gen_setting_for_quantity_Discnt['qty_dsct_activate'] = 'true';
	}

	if ('true' == $gen_setting_for_quantity_Discnt['qty_dsct_activate']) {
		include 'front/qty_discount_front.php';
	}

	if (!isset($gen_setting_for_freq_bgt_products['frq_bgt_activate'])) {
		$gen_setting_for_freq_bgt_products['frq_bgt_activate'] = 'true';
	}

	if ('true' == $gen_setting_for_freq_bgt_products['frq_bgt_activate']) {
		include 'front/frq_bought_front.php';
	}

	if (!isset($gen_setting_for_sales_notification['activatenotify'])) {
		$gen_setting_for_sales_notification['activatenotify'] = 'true';
	}

	if ('true' == $gen_setting_for_sales_notification['activatenotify']) {
		include 'front/sales_notification_front.php';
	}

	if (!isset($gen_setting_for_upsell_products['activateupsell'])) {
		$gen_setting_for_upsell_products['activateupsell'] = 'true';
	}

	if ('true' == $gen_setting_for_upsell_products['activateupsell']) {
		include 'front/upsell_front.php';
	}

	if (!isset($gen_setting_for_price_negotiation['activate_offer'])) {
		$gen_setting_for_price_negotiation['activate_offer'] = 'true';
	}

	if ('true' == $gen_setting_for_price_negotiation['activate_offer']) {
		include 'front/price_negotiation_front.php';
	}
}


function frq_bgt_search_productss_for_instock_only() {

	if ( isset( $_REQUEST['q'] ) ) {
		$pro = sanitize_text_field( $_REQUEST['q'] );
	} else {
		$pro = '';
	}

	$data_array = array();
	$args       = array(
		'post_type'   => array( 'product', 'product_variation' ),
		'post_status' => 'publish',
		'numberposts' => -1,
		's'           => $pro,
	);

	$pros = get_posts( $args );

	if ( ! empty( $pros ) ) {
		foreach ( $pros as $proo ) {
			$product = wc_get_product( $proo->ID );


			if ( $product->is_type( 'external' ) || $product->is_type( 'variable' ) ) {
				continue;
			}

			if ( ! $product->is_in_stock() ) {
				continue;
			}

			if ( $product->is_type( 'variation' ) ) {
				$parent = wc_get_product( $product->get_parent_id() );
				$variation_attributes = wc_get_formatted_variation( $product, true );
				$title = $parent->get_name() . ' - ' . $variation_attributes;
			} else {
				$title = $product->get_name();
			}

			$title = ( mb_strlen( $title ) > 100 ) ? mb_substr( $title, 0, 99 ) . '...' : $title;

			$data_array[] = array( $proo->ID, $title );
		}
	}

	echo wp_json_encode( $data_array );
	wp_die();
}



add_filter( 'plugin_action_links', 'absbssss_estimated_action_links' , 10, 2 );
function absbssss_estimated_action_links( $links, $file ) {
	
	if ( 'advanced-sales-booster-for-woocommerce/advanced-sales-booster-for-woocommerce.php' == $file ) {
		
		$settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=absb' ) . '">' . esc_html__( 'Configure Rules & Settings', 'woocommerce' ) . '</a>';

		$rules = '<a href="' . admin_url( 'edit.php?post_type=absb_custom_offers' ) . '">' . esc_html__( 'Price Offers', 'woocommerce' ) . '</a>';

		array_unshift( $links, $settings, $rules);

	}


	return (array) $links;
}


function frq_bgt_search_productss_only_for_frq_bgt() {


	if (isset($_REQUEST['q'])) {
		$pro = sanitize_text_field( $_REQUEST['q'] );
	} else {
		$pro = '';
	}

	$data_array = array();
	$args       = array(
		'post_type' => array('product', 'product_variation'),
		'post_status' => 'publish',
		'numberposts' => -1,
		's' =>  $pro,

	);
	$pros       = get_posts($args);

	if ( !empty($pros)) {

		foreach ($pros as $proo) {
			$product=wc_get_product($proo->ID);
			if (isset($_REQUEST['main_product'])) {
				if ( 'external' != $product->get_type() && 'variable' != $product->get_type() && $proo->ID != $_REQUEST['main_product'] ) {

					if ( 'variation' == $product->get_type() ) {
						$var_custom_attributes = $product->get_variation_attributes();
						if (count($var_custom_attributes) > 2) {
							$attribute = wc_get_formatted_variation( $var_custom_attributes, true );
							$title = $proo->post_title . ' ' . $attribute;						
						} else {
							$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
						}

					} else {
						$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
					}

					// $title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
					$data_array[] = array( $proo->ID, $title );
				}
			}

		}
	}

	echo wp_json_encode( $data_array );

	wp_die();
}


function creating_wp_editor_for_mailing_option() {

	?>
	<table class="absb_rule_tables temp_table" style="width: 98% !important; margin-left: 1% !important;" >
		<tr> 
			<td style="width:40%;"> 
				<strong>Template Name</strong>
			</td> 
			<td style="width:50%;"> <input type="text" name="temp_name" id="tempname" class="input_type temp_name" style="width:49% !important;"> </td><td style="width:10%;">

			</td>
		</tr>  
		<tr>  
			<td>      
				<strong>Email Subject</strong> 
			</td>  
			<td>
				<input type="text" name="temp_name" id="e_subject" class="input_type e_subject" style="width:49%;">
			</td>  
			<td> 
			</td> 
		</tr>
		<tr> 
			<td> 
				<strong>Write Your Email Content</strong>
			</td>
			<td>
				<?php 
				$settings = array( 
					'editor_height' => 150,
					'textarea_rows' => 5
				);
				wp_editor( '', 'mailig_content', $settings ); 
				?>
			</td>
		</tr>
		<tr>
			<td></td>
			<td></td>

			<td>
				<button type="button" class="del_template" style=" border:1px solid red; padding:9px 30px; background-color:white; color:red; cursor:pointer; border-radius:4px;"> Remove</button>
			</td>
		</tr>
	</table>


	<?php
	wp_die();
}



function frq_bgt_search_productss_for_price_negotiate() {

	if (isset($_REQUEST['q'])) {
		$pro = sanitize_text_field( $_REQUEST['q'] );
	} else {
		$pro = '';
	}

	$data_array = array();
	$args       = array(
		'post_type' => array('product', ''),
		'post_status' => 'publish',
		'numberposts' => -1,
		's' =>  $pro,

	);
	$pros       = get_posts($args);

	if ( !empty($pros)) {

		foreach ($pros as $proo) {
			$product=wc_get_product($proo->ID);
			if ( 'external' != $product->get_type()) {

				$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				$data_array[] = array( $proo->ID, $title );
			}

		}
	}

	echo wp_json_encode( $data_array );

	wp_die();
}


function absb_view_request_record_on_my_account_page() {
	if (isset($_REQUEST['id_to_ammend'])) {
		$offer_to_show_id=sanitize_text_field($_REQUEST['id_to_ammend']);
	}

	if (is_user_logged_in()) {
		$current_userr_id_is=get_current_user_ID();
	}
	?>

	<div class="popup_offer_statusss" style="width: 100%; display: inline-flex;">
		<div style="width: 50%;">
			<?php

			if ('pending' == get_post_meta($offer_to_show_id, 'status', true)) {
				$bgclr='lightgrey';
				$clr='black';
			} else if ('rejected' == get_post_meta($offer_to_show_id, 'status', true)) {
				$bgclr='red';   
				$clr='white';
			} else {
				$bgclr='green';

				$clr='white';
			}
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_post_meta($offer_to_show_id, 'product_id', true) ), 'single-post-thumbnail' );
			if (empty($image) || ''==$image[0]) {
				$placeholder_image = get_option( 'woocommerce_placeholder_image' );
				$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
				$image=$image[0];
			} else {
				$image=$image[0];
			}

			$product=wc_get_product(get_post_meta($offer_to_show_id, 'product_id', true));
			?>
			<table style="width: 70%;">
				<tr>
					<td>
						<img src="<?php echo filter_var($image); ?> " style="margin-right: 10px;width: 100%;height: auto;" >
						<?php
						$product_id = get_post_meta( $offer_to_show_id, 'product_id', true );
						$product = wc_get_product( $product_id );

						if ( $product ) {
							if ( $product->is_type( 'variation' ) ) {
								$parent = wc_get_product( $product->get_parent_id() );
								$variation_attributes = wc_get_formatted_variation( $product, true );
								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {
								$title = $product->get_name();
							}
							?>
							<center><strong><?php echo esc_html( $title ); ?></strong></center>
							<?php
						}
						?>

					</td>
					<td></td>
				</tr>

			</table>
		</div>  
		<div style="width: 50%;">

			<table style="width:70%;margin: unset !important;" class="">



				<tr>
					<td>
						<strong >Request ID</strong>
					</td>
					<td>
						<?php 
						echo filter_var(get_post_meta($offer_to_show_id, 'name', true) . '-' . get_post_meta($offer_to_show_id, 'user_id', true) . '-' . get_post_meta($offer_to_show_id, 'product_id', true) );  
						?>

					</td>
				</tr>

				<tr>
					<td>
						<strong >Status</strong>
					</td>
					<td><span   style="padding: 5px 10px 5px 10px;border-radius: 3px;font-weight: 400;background-color: <?php echo filter_var($bgclr); ?>;color: <?php echo filter_var($clr); ?>;" type="button">
						<?php 
						if ( 'accepted' == get_post_meta($offer_to_show_id, 'status', true)) {
							$end_time=get_post_meta($offer_to_show_id, 'changeprice_till', true);
							if (time()>$end_time) {
								echo 'Expired';
							} else {
								echo filter_var(ucfirst(get_post_meta($offer_to_show_id, 'status', true))); 
							}
						} else {
							echo filter_var(ucfirst(get_post_meta($offer_to_show_id, 'status', true)));
						}

						// echo filter_var(ucfirst(get_post_meta($offer_to_show_id, 'status', true)));  
						?>

					</span></td>
				</tr>
				<tr>
					<td>
						<strong>Requested Quantity</strong>
					</td>
					<td>
						<?php 
						echo filter_var(get_post_meta($offer_to_show_id, 'qty', true));  
						?>

					</td>
				</tr>
				<tr>
					<td>
						<strong>Requested Price</strong>
					</td>
					<td>
						<?php 
						echo filter_var(wc_price(get_post_meta($offer_to_show_id, 'uprice', true)));  
						?>

					</td>
				</tr>
				<?php if ( 'accepted' == get_post_meta($offer_to_show_id, 'status', true)) { ?>
					<tr>
						<td>
							<strong>Accepted Price</strong>
						</td>
						<td>
							<?php 
							echo filter_var(wc_price(get_post_meta($offer_to_show_id, 'absb_approved_price', true)));  
							?>

						</td>
					<?php } ?>

				</tr>

				<?php 
				if ('accepted' == get_post_meta($offer_to_show_id, 'status', true) ) {

					?>
					<tr>
						<td>
							<strong>Discount Validity</strong>
						</td>
						<td>
							<?php 

							echo filter_var(get_post_meta($offer_to_show_id, 'changeprice_till_Admin', true)/3600 . ' Hour (s) after <br> Approval') ;  
							?>

						</td>
					</tr>

					<?php
				}
				?>



			</table>
		</div>
	</div>
	<?php

	wp_die();
}


function absb_view_offer_popup() {

	if (isset($_REQUEST['id_to_ammend'])) {
		$offer_to_show_id=sanitize_text_field($_REQUEST['id_to_ammend']);
	}

	if (is_user_logged_in()) {
		$current_userr_id_is=get_current_user_ID();
	}

	?>

	<div style="width: 100%; display: inline-flex;">
		<div style="width: 50%;">
			<?php

			if ('pending' == get_post_meta($offer_to_show_id, 'status', true)) {
				$bgclr='lightgrey';
				$clr='black';
			} else if ('rejected' == get_post_meta($offer_to_show_id, 'status', true)) {
				$bgclr='red';   
				$clr='white';
			} else {
				$bgclr='green';

				$clr='white';
			}
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_post_meta($offer_to_show_id, 'product_id', true) ), 'single-post-thumbnail' );
			if (empty($image) || ''==$image[0]) {
				$placeholder_image = get_option( 'woocommerce_placeholder_image' );
				$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
				$image=$image[0];
			} else {
				$image=$image[0];
			}

			$product=wc_get_product(get_post_meta($offer_to_show_id, 'product_id', true));
			?>
			<table style="width: 70%;">
				<tr>
					<td>
						<img src="<?php echo filter_var($image); ?> " style="margin-right: 10px;width: 150px !important;height: 150px !important;" >
						<?php
						$product_id = get_post_meta( $offer_to_show_id, 'product_id', true );
						$product    = wc_get_product( $product_id );

						if ( $product ) {
							if ( $product->is_type( 'variation' ) ) {
								$parent = wc_get_product( $product->get_parent_id() );
								$variation_attributes = wc_get_formatted_variation( $product, true );
								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {
								$title = $product->get_name();
							}
							?>
							<strong><?php echo esc_html( $title ); ?></strong>
							<?php
						}
						?>


					</td>
					<td></td>
				</tr>
				<tr>
					<td>
						<strong>Product Actual Price</strong>
					</td>
					<td>

						<?php echo filter_var(wc_price($product->get_price())); ?>
					</td>
				</tr>
			</table>
		</div>  
		<div style="width: 50%;">

			<table style="width:70%;margin: unset !important;" class="">



				<tr>
					<td>
						<strong >Request ID</strong>
					</td>
					<td>
						<?php
						echo filter_var(get_post_meta($offer_to_show_id, 'name', true) . '-' . get_post_meta($offer_to_show_id, 'user_id', true) . '-' . get_post_meta($offer_to_show_id, 'product_id', true) );  
						?>

					</td>
				</tr>

				<tr>
					<td>
						<strong >Status</strong>
					</td>
					<td><span   style="padding: 5px 10px 5px 10px;border-radius: 3px;font-weight: 400;background-color: <?php echo filter_var($bgclr); ?>;color: <?php echo filter_var($clr); ?>;" type="button">
						<?php
						if ( 'accepted' == get_post_meta($offer_to_show_id, 'status', true)) {
							$end_time=get_post_meta($offer_to_show_id, 'changeprice_till', true);
							if (time()>$end_time) {
								echo 'Expired';
							} else {
								echo filter_var(ucfirst(get_post_meta($offer_to_show_id, 'status', true)));
							}
						} else {
							echo filter_var(ucfirst(get_post_meta($offer_to_show_id, 'status', true)));
						}

						?>

					</span></td>
				</tr>
				<tr>
					<td>
						<strong>Requested Quantity</strong>
					</td>
					<td>
						<?php
						echo filter_var(get_post_meta($offer_to_show_id, 'qty', true));  
						?>

					</td>
				</tr>
				<tr>
					<td>
						<strong>Requested Price</strong>
					</td>
					<td>
						<?php
						echo filter_var(wc_price(get_post_meta($offer_to_show_id, 'uprice', true)));  
						?>

					</td>
				</tr>
				<?php if ( 'accepted' == get_post_meta($offer_to_show_id, 'status', true)) { ?>
					<tr>
						<td>
							<strong>Accepted Price</strong>
						</td>
						<td>
							<?php
							echo filter_var(wc_price(get_post_meta($offer_to_show_id, 'absb_approved_price', true)));  
							?>

						</td>
					<?php } ?>

				</tr>

				<?php 
				if ('accepted' == get_post_meta($offer_to_show_id, 'status', true) ) {

					?>
					<tr>
						<td>
							<strong>Discount Validity</strong>
						</td>
						<td>
							<?php
							echo filter_var(get_post_meta($offer_to_show_id, 'changeprice_till_Admin', true)/3600 . ' Hour (s) after <br> Approval') ;  
							?>

						</td>
					</tr>

					<?php
				}
				?>
			</table>
		</div>
	</div>
	<?php

	wp_die();
}


function absb_save_post_daata( $post_id ) {    


	if (get_post_type() != 'absb_custom_offers') {
		return;
	}

	$emailll = get_post_meta($post_id, 'email', true);
	$product = wc_get_product(get_post_meta($post_id, 'product_id', true));

	// $pro_name = get_the_title(get_post_meta($post_id, 'product_id', true));



	if ( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$variation_attributes = wc_get_formatted_variation( $product, true );
			$pro_name = $parent->get_name() . ' - ' . $variation_attributes;
		} else {
			$pro_name = $product->get_name();
		}
	} else {
		$pro_name = '';
	}



	$email_settings = get_option('absb_ofer_email_contnets_datum');
	if ('absb_custom_offers' != get_post_type($post_id)) {
		return;
	}

	

	if (isset($_REQUEST['dis_valid'])) {
		$discount_validity=sanitize_text_field($_REQUEST['dis_valid']); 
		update_post_meta($post_id, 'changeprice_till', time()+$discount_validity);
		update_post_meta($post_id, 'changeprice_till_Admin', $discount_validity);

	}

	if (isset($_REQUEST['status_req']) ) {
		if ('accepted' == $_REQUEST['status_req']) {
			$subject = $email_settings['ofr_accepted_subject'];

			$body = $email_settings['ofr_accepted_content'];
			if (str_contains($body, '{product_name}')) {
				$body = str_replace('{product_name}', $pro_name, $body );   

			}

			$absb_approved_price = '';
			if (isset($_REQUEST['appr_price'])) {
				$absb_approved_price = filter_var($_REQUEST['appr_price']);
			}

			if (str_contains($body, '{Approved_Discount}')) {
				$body = str_replace('{Approved_Discount}', $absb_approved_price, $body);   

			}

			if (str_contains($body, '{valid_till}')) {
				$body = str_replace('{valid_till}', $discount_validity/3600 . ' Hour (s)', $body );   

			}


		} else if ('rejected' == $_REQUEST['status_req']) {
			$subject = $email_settings['ofr_rejeceted_subject'];
			$body = $email_settings['ofr_rejected_content'];
			if (str_contains($body, '{product_name}')) {
				$body = str_replace('{product_name}', $pro_name, $body );   

			}
		}
	}

	$to = $emailll;


	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
	$headers .= 'From: abc@gmail.com' . "\r\n";

	if ('pending'==$_REQUEST['status_req']) {
		$a = '';
	} else {
		if (isset($_REQUEST['status_req']) && isset($_REQUEST['appr_price'])) {
			if (get_post_meta($post_id, 'status', true) == $_REQUEST['status_req'] && get_post_meta($post_id, 'absb_approved_price', true)  != $_REQUEST['appr_price']) {


				wp_mail( $to, $subject, $body, $headers );
			} else if (get_post_meta($post_id, 'status', true) == $_REQUEST['status_req'] &&  get_post_meta($post_id, 'absb_approved_price', true) == $_REQUEST['appr_price']) {
				$a = ' ';


			} else if (get_post_meta($post_id, 'status', true) != $_REQUEST['status_req'] &&  get_post_meta($post_id, 'absb_approved_price', true)  == $_REQUEST['appr_price']) {

				wp_mail( $to, $subject, $body, $headers );

			} else if (get_post_meta($post_id, 'status', true) != $_REQUEST['status_req']  &&  get_post_meta($post_id, 'absb_approved_price', true) != $_REQUEST['appr_price'] ) {

				wp_mail( $to, $subject, $body, $headers );
			}
		}
	}


	if (isset($_REQUEST['appr_price'])) {
		$absb_approved_price=sanitize_text_field($_REQUEST['appr_price']);
		update_post_meta($post_id, 'absb_approved_price', $absb_approved_price);
	}
	if (isset($_REQUEST['status_req'])) {
		$req_status=sanitize_text_field($_REQUEST['status_req']);   
		update_post_meta($post_id, 'status', $req_status);
	}



	$user_Array=array(
		'post_id' => $post_id,
		'product_id' => get_post_meta($post_id, 'product_id', true),
		'qty' => get_post_meta($post_id, 'qty', true),
		'absb_approved_price' => $absb_approved_price,
		'status' => $req_status,
		'time' => time(),
	);


	$prev=get_user_meta(get_post_meta($post_id, 'user_id', true), 'is_approved', true);
	if ('' == $prev) {
		$prev=array();
	}
	$prev[]=$user_Array;
	update_user_meta(get_post_meta($post_id, 'user_id', true), 'is_approved', $prev);
}



function absb_creating_meta_box() {
	add_meta_box( 'absb_request_dets', 'Offer Details', 'absb__mof_meta_box_offer_details', 'absb_custom_offers', 'normal', 'low' );
}

function absb__mof_meta_box_offer_details() {



	$post_id = get_the_ID();


	if (is_user_logged_in()) {
		$current_userr_id_is=get_current_user_ID();

	} else {
		$current_userr_id_is='empty';

	}
	wp_enqueue_style('date_picker_css_absbaaaaassds', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', false, '1.0', 'all');
	$offer_to_show_id=get_the_ID();
	$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_post_meta($offer_to_show_id, 'product_id', true) ), 'single-post-thumbnail' );
	if (empty($image) || ''==$image[0]) {
		$placeholder_image = get_option( 'woocommerce_placeholder_image' );
		$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
		$image=$image[0];
	} else {
		$image=$image[0];
	}
	$product=wc_get_product(get_post_meta($offer_to_show_id, 'product_id', true));


	?>
	<div>

		<img src="<?php echo filter_var($image); ?> " style="margin-right: 10px;width: 150px !important;height: 150px !important;" ><br>
		<strong><?php echo filter_var(get_the_title($product->get_ID()) ); ?></strong><br><br><br>
	</div>
	<div style=" display: inline-flex; width: 98%; border: 1px solid #ae7b3b; padding:1%; border-radius: 10px; margin-bottom: 3%;">
		<div style="width: 50%;">



			<h2 style="background-color: lightgrey;">Offer Details</h2>

			<table class="absb_rule_tables" style="width: 100%;">
				<tr>
					<td>
						<strong>Product Name</strong>
					</td>
					<td>
						<?php
						$product_id = get_post_meta( $post_id, 'product_id', true );
						$productoobligation = wc_get_product( $product_id );

						if ( $productoobligation ) {
							if ( $productoobligation->is_type( 'variation' ) ) {
								$plogid = $productoobligation->get_parent_id();
								$parent = wc_get_product( $plogid );
								$variation_attributes = wc_get_formatted_variation( $productoobligation, true );
								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {
								$plogid = $product_id;
								$title = $productoobligation->get_name();
							}
							?>
							<a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . $plogid . '&action=edit' ) ); ?>">
								<span><?php echo esc_html( $title ); ?></span>
							</a>
							<?php
						}
						?>
					</td>

				</tr>
				<tr>
					<td>
						<strong>Actual Price</strong>
					</td>
					<td>
						<?php echo filter_var(wc_price($product->get_price())); ?>
					</td>
				</tr>

				<tr>
					<td>
						<strong>Requested Price</strong>
					</td>
					<td>
						<?php echo filter_var(wc_price(get_post_meta($offer_to_show_id, 'uprice', true))); ?>
					</td>
				</tr>

				<tr>
					<td>
						<strong>Desired Quantity</strong>
					</td>
					<td>
						<?php echo filter_var(get_post_meta($offer_to_show_id, 'qty', true)); ?>
					</td>
				</tr>


			</table>
		</div>

		<div style="width: 33%;">
			<h2 style="background-color: lightgrey;">Customer's Details</h2>
			<table style="margin-top: 3%; width: 100%;">
				<tr>
					<td style="width: 33%;">
						<strong>Customer ID</strong>
					</td>
					<td style="width: 67%;">
						<?php echo filter_var(get_post_meta($offer_to_show_id, 'user_id', true)); ?>
					</td>
				</tr>

				<tr>
					<td>
						<strong>Customer Name</strong>
					</td>
					<td>
						<?php echo filter_var(get_post_meta($offer_to_show_id, 'name', true)); ?>
					</td>
				</tr>

				<tr>
					<td>
						<strong>Customer's Email</strong>
					</td>
					<td>
						<?php echo filter_var(get_post_meta($offer_to_show_id, 'email', true)); ?>
					</td>
				</tr>

				<tr>
					<td>
						<strong>Additional Note</strong>
					</td>
					<td>
						<?php echo filter_var(get_post_meta($offer_to_show_id, 'req_note', true)); ?>
					</td>
				</tr>


			</table>
		</div>
		<div style="width: 33%;">
			<h2 style="text-align: center; background-color: lightgrey;" >Action</h2>
			<table style="margin-top: 3%; width: 100%;">
				<tr>
					<td style="width: 33%;">
						<strong>Status</strong>
					</td>
					<td style="width: 67%;">
						<select id="status_req" name="status_req" style="width: 100%;">
							<option value="pending"
							<?php
							if ('pending' == get_post_meta($post_id, 'status', true)) {
								echo filter_var('selected');
							}
							?>
							>Pending</option>
							<option value="accepted"
							<?php
							if ('accepted' == get_post_meta($post_id, 'status', true)) {
								echo filter_var('selected');
							}
							?>
							>Accepted</option>
							<option value="rejected"
							<?php
							if ('rejected' == get_post_meta($post_id, 'status', true)) {
								echo filter_var('selected');
							}
							?>
							>Rejected</option>
						</select>
					</td>               
				</tr>
				<tr>
					<td>
						<strong>Approved Price</strong>
					</td>
					<td>
						<input type="number" name="appr_price" id="appr_price" style="width: 100%;" value="<?php echo filter_var(get_post_meta($post_id, 'absb_approved_price', true )); ?>">
					</td>
				</tr>
				<tr>
					<td>
						<strong>Discount Validity</strong>
					</td>
					<td>
						<select id="dis_valid" name="dis_valid" style="width: 100%;">
							<option value="3600"
							<?php
							if ('3600' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>1 Hour after Approval</option>
							<option value="7200"
							<?php
							if ('7200' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>2 Hours after Approval</option>
							<option value="10800"
							<?php
							if ('10800' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>3 Hours after Approval</option>
							<option value="21600"
							<?php
							if ('21600' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>6 Hours after Approval</option>
							<option value="43200"
							<?php
							if ('43200' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>12 Hours after Approval</option>
							<option value="Forever"
							<?php
							if ('Forever' == get_post_meta($post_id, 'changeprice_till_Admin', true)) {
								echo filter_var('selected');
							}
							?>
							>Forever</option>

						</select>
					</td>
				</tr>


			</table>
		</div>

	</div>




	<script type="text/javascript">

		jQuery('body').on('click', '#publish', function(){
			if (jQuery('#appr_price').val() == '' && jQuery('#status_req').val() == 'accepted'   ) {
				alert("Please fill Approved Price field")
				event.preventDefault();
			}
		})
	</script>


	<?php
}

function absb_sending_price_request_to_db() { 


	if (is_user_logged_in()) {
		$current_userr_id_is=get_current_user_ID();
		$datau=get_userdata(get_current_user_ID());
		$display_name=$datau->display_name;
	} else {
		if (isset($_REQUEST['req_email'])) {

			$user = get_user_by( 'email', filter_var($_REQUEST['req_email']) );
		}

		if ($user) {
			$current_userr_id_is= $user->ID;
			$display_name= $user->display_name;
		} else {
			$current_userr_id_is='empty';
			$display_name='Guest';

		}


	}

	if (isset($_REQUEST['req_email'])) {

		$emailll=sanitize_text_field($_REQUEST['req_email']);
	}


	if (isset($_REQUEST['req_name'])) {

		$namee=sanitize_text_field($_REQUEST['req_name']);
	}


	if (isset($_REQUEST['req_quantity'])) {

		$qtyyy=sanitize_text_field($_REQUEST['req_quantity']);
	}


	if (isset($_REQUEST['req_price'])) {

		$upriceeee=sanitize_text_field($_REQUEST['req_price']);
	}


	if (isset($_REQUEST['product_id'])) {
		$product_iddd=sanitize_text_field($_REQUEST['product_id']);
	}



	if (isset($_REQUEST['req_note'])) {
		$txtareaaaa=sanitize_text_field($_REQUEST['req_note']);
	}
	$gen_setting_for_price_negotiation = get_option('absb_saved_general_settings_for_price_negotiate');
	$email_settings = get_option('absb_ofer_email_contnets_datum');


	remove_action('save_post', 'absb_save_post_daata');

	$id=wp_insert_post(array(
		'post_status' => 'publish',
		'post_type' => 'absb_custom_offers',
		'post_title' => $namee . '-' . $current_userr_id_is . '-' . $product_iddd,
		'post_content' => ''
	));

	update_post_meta($id, 'user_id', $current_userr_id_is);
	update_post_meta($id, 'status', 'pending');
	update_post_meta($id, 'name', $namee);
	update_post_meta($id, 'product_id', $product_iddd);
	update_post_meta($id, 'email', $emailll);
	update_post_meta($id, 'qty', $qtyyy);
	update_post_meta($id, 'uprice', $upriceeee);
	update_post_meta($id, 'req_note', $txtareaaaa);
	// update_post_meta($id, 'rule_id_key', $rule_id_keyyy);


	$to = $emailll;
	$subject = $email_settings['ofr_recieved_mail_subject'];
	$body = $email_settings['ofr_recieved_mail_content'];

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
	$headers .= 'From: abc@gmail.com' . "\r\n";

	wp_mail( $to, $subject, $body, $headers );
	echo filter_var($id);

	wp_die();
}

function absb_delete_rule_data_db1() {
	$labels = array(
		'name' => 'Price Offers',
		'singular_name' => 'Price Negotiation Request',
		'add_new' => 'Price Negotiation Request',
		'add_new_item' => 'Add New Price Offer',
		'edit_item' => 'Edit Price Offer',

		'new_item' => 'New Price Offer',
		'view_item' => 'View Price Offer',
		'search_items' => 'Search Price Offer',
		'not_found' =>  'No Price Offer found',
		'not_found_in_trash' => 'No Price Offer in the trash',
		'parent_item_colon' => '',
	);

	register_post_type( 'absb_custom_offers', array(
		'labels' => $labels,

		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'exclude_from_search' => true,
		'query_var' => true,
		'show_in_menu'        => 'woocommerce', 
		'rewrite' => true,
		'capability_type' => 'post',
		'capabilities' => array(
			'create_posts' => false, 
		),
		'map_meta_cap' => true,
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => 10,
		'supports' => array( 'title' )
	));
}

function absb_saving_email_templates_for_offer_mod() {

	$offer_email_templates = update_option('absb_ofer_email_contnets_datum', $_REQUEST);
	wp_die();
}
function absb_updating_eidted_rule_for_offer() {

	$allrules_offer=get_option('absb_offer_rule_settings');


	if ( '' == $allrules_offer ) {
		$allrules_offer=array();
	}

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}
	$allrules_offer[$index]=$_REQUEST;

	update_option('absb_offer_rule_settings', $allrules_offer);
	wp_die();
}

function absb_offer_popup_for_edit() {

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}
	$offer_all_rules=get_option('absb_offer_rule_settings');

	$to_be_sent=$offer_all_rules[$index];
	$absb_product_category_html='';
	$absb_parentid = get_queried_object_id();
	$absb_args = array(
		'numberposts' => -1,
		'taxonomy' => 'product_cat',
	);
	$absb_terms = get_terms($absb_args);
	if ( $absb_terms ) {   
		foreach ( $absb_terms as $absb_term1 ) {
			$selected = '';
			if ('categories'==$to_be_sent['offer_appliedon'] && is_array($to_be_sent['offer_procat_ids']) && in_array($absb_term1->term_id , $to_be_sent['offer_procat_ids'])) {
				$selected = 'selected';
			}

			$absb_product_category_html = $absb_product_category_html . '<option class="absb_catopt" value="' . $absb_term1->term_id . '" ' . $selected . ' >' . $absb_term1->name . '</option>';

		}  
	}
	?>
	<h2 style="text-align: left;">Basic Settings</h2>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">
		<tr>
			<td style="text-align: left;"><strong>Rule Name</strong>

				<input type="text" id="offer_rule_name1" class="absbmsgbox" style="width:70%; padding:2px;" value="<?php echo esc_attr(( '' == $to_be_sent['offer_rule_name'] )) ? esc_attr( 'Rule # ' . ( $index+1 )) : esc_attr( $to_be_sent['offer_rule_name'] ); ?> ">

			</td>
			<td style="text-align:right;"><strong>Activate Rule</strong>

				<label class="switch">
					<input type="checkbox" id="offer_activate_rule1" 
					<?php
					if ('true' == $to_be_sent['activate_offer_rule']) {
						echo 'checked';
					}
					?>
					>
					<span class="slider"></span>
				</label>
			</td>
		</tr>
	</table>
	<h2 style="text-align: left;">Select Products/Categories Settings</h2>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

		<tr>
			<td style="width: 30%;">
				<strong>Select One</strong>
			</td>

			<td style="width: 70%;">
				<select id="absb_offer_appliedon1" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
					<option value="products"value
					<?php
					if ('products' == $to_be_sent['offer_appliedon']) {
						echo 'selected';
					}
					?>
					>Products</option>
					<option value="categories"
					<?php
					if ('categories' == $to_be_sent['offer_appliedon']) {
						echo 'selected';
					}
					?>
					>Categories</option>

				</select>
			</td>
		</tr>
		<tr>
			<td id="offer_label_for_options1" style="width: 30%;">
				<strong>Select Product/Category <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td id="offer_11" style="width: 70%;" >
				<select multiple id="offer_select_product1" class="absbselect" name="multi[]">


					<?php

					if ('products'==$to_be_sent['offer_appliedon'] && is_array($to_be_sent['offer_procat_ids'])) {

						foreach ($to_be_sent['offer_procat_ids'] as $keyy => $valuee) {
							echo '<option value="' . esc_attr(trim($valuee)) . '" selected >' . esc_html(get_the_title($valuee)) . '</option>';
						}

					}
					?>
				</select>
			</td>
			<td id="offer_21" style="display: none; width: 70%;">
				<select multiple id="offer_select_category1" name="multi2[]" class="absbselect upsell_cat_select">
					<?php echo filter_var($absb_product_category_html); ?>
				</select>
			</td>
		</tr>


	</table>


	<h2 style="text-align: left;">Select User Roles</h2>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

		<?php 
		global $wp_roles;
		$absb_all_roles = $wp_roles->get_names();
		?>
		<tr>
			<td style="width: 30%;">
				<strong>User Roles</strong>
			</td>
			<td style="width: 70%;">


				<?php 
				global $wp_roles;
				$absb_all_roles = $wp_roles->get_names();

				?>
				<select multiple id="offer_select_roles1"  style="width: 100% !important; padding: 3px;  max-width: 100% !important;">
					<?php
					foreach ($absb_all_roles as $key_role => $value_role) {
						?>
						<option value="<?php echo filter_var($key_role); ?>"
							<?php

							if ('' == $to_be_sent['offer_roles']) {


								echo 'All Roles';
							} else if (isset($to_be_sent['offer_roles']) && in_array($key_role, $to_be_sent['offer_roles'])) {
								echo 'selected';
							}
							?>
							>
							<?php echo filter_var(ucfirst($value_role)); ?>

						</option>
						<?php
					}

					?>

				</select>   
				<br><i style="color: #007cba;">(Important : Leaving empty will be considered as All Roles Allowed.)</i>

			</td>       
		</tr>


		<tr>
			<td>
				<strong>Allow Guest User</strong>
			</td>
			<td>
				<label class="switch">
					<input type="checkbox" id="absb_prc_neg_is_guest1" 
					<?php
					if ( 'true' == $to_be_sent['absb_prc_neg_is_guest']) {
						echo 'checked';
					}
					?>
					>
					<span class="slider"></span>
				</label>
			</td>
		</tr>


	</table>
	<script type="text/javascript">
		jQuery('#offer_select_roles1').select2();
		jQuery('#offer_select_category1').select2();
		jQuery('#offer_select_product1').select2({

			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					return {
						q: params.term, 
						action: 'frq_bgt_search_productss_for_price_negotiate',  

					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			multiple: true,
			placeholder: 'Choose Products',
			minimumInputLength: 3 

		});


		var absb_selected123=jQuery('#absb_offer_appliedon1').val();
		if ('products' == absb_selected123) {
			jQuery('#offer_label_for_options1').show();
			jQuery('#offer_11').show();
			jQuery('#offer_21').hide();
		}
		else if ('categories' == absb_selected123) {
			jQuery('#offer_label_for_options1').show();
			jQuery('#offer_11').hide();
			jQuery('#offer_21').show(); 
		}
	</script>
	<style type="text/css">
		.table_ppp{
			border-collapse: collapse;
			width: 100%;
		}

		.table_ppp th, .table_ppp td {
			border: 1px solid #ccc;
			padding: 6px 10px;
			text-align: left;
		}
	</style>

	<?php

	wp_die();
}

function absb_offer_deleting_rule() {

	$indexnumber= ( isset( $_REQUEST['index'] ) ) ? filter_var($_REQUEST['index']) : '';
	$data=get_option('absb_offer_rule_settings');
	unset($data[$indexnumber]);
	$data=array_values($data);
	update_option('absb_offer_rule_settings', $data);
	wp_die();
}

function absbs_offer_get_all_rules_from_db_fordatatable() {

	$offer_rule_data=get_option('absb_offer_rule_settings');

	if ( '' == $offer_rule_data ) {
		$offer_rule_data=array();
	}
	$return_json=array();

	foreach ($offer_rule_data as $key => $value) {
		if ('true' == $value['activate_offer_rule']) {
			$html='Active <i style="color:green;" class="fa fa-check" aria-hidden="true"></i>';
		} else {
			$html='Deactive <i style="color:red;" class="fa fa-remove"></i>';
		}

		if ('' == $value['offer_rule_name']) {
			$rulename = 'Rule # ' . ( $key+1 );
		} else {
			$rulename = $value['offer_rule_name'];
		}
		
		if ('products' == $value['offer_appliedon']) {
			$appliedon = 'Products';
		} else if ('categories' == $value['offer_appliedon'] ) {
			$appliedon = 'Categories';
		}

		if ('' == $value['offer_roles']) {
			$roles = 'All Roles';
		} else {
			$roles = $value['offer_roles'];
		}

		$elt_row = array(

			'Rule Name' => $rulename,
			'Applied on' => $appliedon,               
			'Allowed Roles' => $roles,
			'Status' =>  $html,

			'Edit / Delete' => $key,
		);
		$return_json[] = $elt_row;

	}

	echo json_encode(array('data' => $return_json));
	wp_die();
}

function absb_saving_rule_for_offer() {

	$offer_rules_settingss=get_option('absb_offer_rule_settings');

	if (''==$offer_rules_settingss) {
		$offer_rules_settingss=array();
	}

	$offer_rules_settingss[]=$_REQUEST;
	update_option('absb_offer_rule_settings', $offer_rules_settingss);
	wp_die();
}

function absb_saving_offers_general_settingss() {

	update_option('absb_saved_general_settings_for_price_negotiate', $_REQUEST);
	wp_die();

}

function absb_update_upsell_rule() {


	$allrules_upsell=get_option('absb_rule_settings_upsell');


	if ( '' == $allrules_upsell ) {
		$allrules_upsell=array();
	}

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}




	$allrules_upsell[$index]=$_REQUEST;

	update_option('absb_rule_settings_upsell', $allrules_upsell);

	wp_die();
}

function absb_ups_upsell_deleting_rule () {

	$indexnumber= ( isset( $_REQUEST['index'] ) ) ? filter_var($_REQUEST['index']) : '';
	$data=get_option('absb_rule_settings_upsell');
	unset($data[$indexnumber]);
	$data=array_values($data);
	update_option('absb_rule_settings_upsell', $data);

	wp_die();
}


function absb_ups_upsell_popup_for_edit() {


	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}
	$upsells_all_rules=get_option('absb_rule_settings_upsell');

	$to_be_sent=$upsells_all_rules[$index];

	?>


	<h2 style="text-align: left;">Basic Settings</h2>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables" >
		<tr>
			<td style="text-align: left;"><strong>Rule Name</strong>

				<input type="text" name="absb_name" id="upsell_rule_name1" class="absbmsgbox" style="width:70%; padding:2px;" value="<?php echo esc_attr(( '' == $to_be_sent['upsell_rule_name'] )) ? esc_attr( 'Rule # ' . ( $index+1 )) : esc_attr( $to_be_sent['upsell_rule_name'] ); ?> ">



			</td>
			<td style="text-align:right;"><strong>Activate Rule</strong>

				<label class="switch">
					<input type="checkbox" id="upsell_activate_rule1" 
					<?php
					if ( 'true' == $to_be_sent['activate_rule']) {
						echo 'checked';
					}
					?>
					>
					<span class="slider"></span>
				</label>
			</td>
		</tr>
	</table>






	<h2 style="text-align: left;">Set Conditions for Upsell products</h2>


	<button class="button-primary absb_add_conditions1" type="button" style="background-color: green; color: white; padding: 2px 5px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important; float: right; margin-bottom: 1%; margin-right: 10px;">
		<i class="fa fa-fw fa-plus"></i>
	Add Condition(s)</button>



	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp" id="tablemno1">

		<tr>
			<th style="width: 30%;">
				Select 
			</th>
			<th style="width: 30%;">
				Condition
			</th>
			<th style="width: 30%;">
				Conditional Value <span class="required" style="color: red; border:none; font-weight: 300;">*</span>
			</th>

			<th style="width: 10%;">
				Action
			</th>
		</tr>
		<?php
		foreach ($to_be_sent['select_iss'] as $key => $value) {
			?>
			<tr>

				<td style="width: 30%;">
					<select style="width: 99%;" required class="select_one_for_condition1" id="select_one_for_condition_main1" >    

						<option value="cartitems"
						<?php 
						if ('cartitems' == $value) {
							echo 'selected';
						}
						?>
						>Items in Cart</option>
						<option value="subtotal"
						<?php 
						if ('subtotal' == $value) {
							echo 'selected';
						}
						?>
						>Subtotal</option>
						<option value="total"
						<?php 
						if ('total' == $value) {
							echo 'selected';
						}
						?>
						>Total</option>
						<option value="cpn_used"
						<?php 
						if ('cpn_used' == $value) {
							echo 'selected';
						}
						?>
						>Coupon Code Used</option>
						<option value="slctd_product"
						<?php 
						if ('slctd_product' == $value) {
							echo 'selected';
						}
						?>
						>Cart Contains Product</option>
						<option value="slctd_cateory"
						<?php 
						if ('slctd_cateory' == $value) {
							echo 'selected';
						}
						?>
						>Cart Contains Category</option>
						<option value="user_role"
						<?php 
						if ('user_role' == $value) {
							echo 'selected';
						}
						?>
						>User Role</option>
					</select>

				</td>
				<td style="width: 30%;">
					<select style="width: 99%;" required class="conditionn1" id="absb_conditions1">     
						<option value="equals"
						<?php
						if ('equals' == $to_be_sent['conditionn'][$key]) {
							echo 'selected';
						}
						?>
						>Equal To</option>
						<option value="notequal"
						<?php
						if ('notequal' == $to_be_sent['conditionn'][$key]) {
							echo 'selected';
						}
						?>
						>Not Equal To</option>

						<?php if ( 'cartitems' == $value || 'subtotal' == $value || 'total' == $value ) { ?>

							<option value="greater"
							<?php
							if ('greater' == $to_be_sent['conditionn'][$key]) {
								echo 'selected';
							}
							?>
							>Greater Than</option>

							<option value="less"
							<?php
							if ('less' == $to_be_sent['conditionn'][$key]) {
								echo 'selected';
							}
							?>
							>Less Than</option>
						<?php } ?>
					</select>                       
				</td>
				<td style="width: 30%;">

					<div id="absb_upsell_sngl_txt1" class="divs_for_options1"
					<?php
					if ('cpn_used'!= $value && 'cartitems'!=$value && 'subtotal'!=$value && 'total'!=$value ) {

						echo filter_var(' style="display:none; "');
					}
					?>
					>
					<?php
					$input_value = '';
					if ('cpn_used' == $value || 'cartitems' == $value || 'subtotal' == $value || 'total' == $value ) {
						$input_value = $to_be_sent['conditional_val'][$key];
					}
					?>
					<input type="text" class="conditional_val1" required style="width: 99%;" value="<?php echo esc_attr($input_value); ?>">
				</div>


				<div id="select_for_product_div1" class="divs_for_options1" 
				<?php
				if ('slctd_product'!= $value) {

					echo filter_var(' style="display:none; "');
				}
				?>
				>
				<select name="upcell_select_products[]" style="max-width: 99%;width: 99%;font-size: 12px;" id="upcell_select_products1" class="upcell_select_products1">
					<?php
					if ( 'slctd_product' == $value ) {
						$product_id = trim( $to_be_sent['conditional_val'][ $key ] );
						$product    = wc_get_product( $product_id );

						if ( $product ) {
							if ( $product->is_type( 'variation' ) ) {
								$parent = wc_get_product( $product->get_parent_id() );
								$variation_attributes = wc_get_formatted_variation( $product, true );
								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {
								$title = $product->get_name();
							}

							echo '<option value="' . esc_attr( $product_id ) . '" selected>' . esc_html( $title ) . '</option>';
						}
					}
					?>
				</select>

			</div> 


			<?php

			$elt_product_category_html='';
			$elt_parentid = get_queried_object_id();
			$elt_args = array(
				'numberposts' => -1,
				'taxonomy' => 'product_cat',
			);
			$elt_terms = get_terms($elt_args);

			if ( $elt_terms ) {   
				foreach ( $elt_terms as $elt_term1 ) {
					$selected = '';

					if ( 'slctd_cateory' == $value && $elt_term1->term_id == $to_be_sent['conditional_val'][$key] ) {
						$selected = 'selected';
					}
					$elt_product_category_html = $elt_product_category_html . '<option class="ert_catopt" value="' . $elt_term1->term_id . '" ' . $selected . ' >' . $elt_term1->name . '</option>';
				}
			}

			?>
			<div id="select_for_category_div1" class="divs_for_options1" 
			<?php
			if ('slctd_cateory'!= $value) {

				echo filter_var(' style="display:none; "');
			}
			?>
			>
			<select  name="upcell_select_category[]" style="max-width:99%;width: 99%;font-size: 12px;" id="upcell_select_category1"    >
				<?php echo filter_var($elt_product_category_html); ?>

			</select>
		</div>


		<div  id="select_for_role_div1" class="divs_for_options1"
			<?php
			if ('user_role'!= $value) {

				echo filter_var(' style="display:none; "');
			}
			?>
		>


			<?php 
			global $wp_roles;
			$absb_all_roles = $wp_roles->get_names();
			?>

		<select id="upsell_select_roles1"  style="width: 99%;">
			<?php
			foreach ($absb_all_roles as $key_role => $value_role) {
				?>
				<option value="<?php echo filter_var($key_role); ?>"
					<?php
					if ('user_role' == $value && $key_role == $to_be_sent['conditional_val'][$key]) {
						echo 'selected';
					}
					?>
					>
					<?php echo filter_var(ucfirst($value_role)); ?>

				</option>


				<?php
			}
			?>


		</select>
	</div>

</td>
			<?php 
			if (0 != $key) { 
				?>
	<td><button type="button" class="del_range_btn" style=" border:none; background-color:white; color:red; cursor:pointer; border:1px solid red; padding:8px 10px; border-radius:4px; margin-left: 40px;"><i class="fa fa-trash" style="font-size:14px !important;"></i> </button></td>
				<?php 
			}
			?>

</tr>
			<?php
		}

		?>

</table>

<h2 style="text-align: left;">UpSell Products</h2>

<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

	<tr>
		<td style="width: 30%;">
			<strong>Select One</strong>
		</td>

		<td style="width: 70%;">
			<select id="absb_upsell_appliedon1" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
				<option value="products" 
				<?php
				if ('products' == $to_be_sent['appliedon_upsell']) {
					echo 'selected';
				}
				?>
				>Products</option>
				<option value="categories"
				<?php
				if ('categories' == $to_be_sent['appliedon_upsell']) {
					echo 'selected';
				}
				?>
				>Categories</option>

			</select>
		</td>
	</tr>
	<tr>
		<td id="upsell_label_for_options1">
			<strong>Select Product/Category <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
		</td>
		<td id="upsell_11" >
			<select multiple id="upsell_funnel_select_product1" class="absbselect" name="multi[]">

				<?php

				// if ('products'==$to_be_sent['appliedon_upsell'] && is_array($to_be_sent['procat_ids_upsell'])) {

				// 	foreach ($to_be_sent['procat_ids_upsell'] as $keyy => $valuee) {
				// 		echo '<option value="' . esc_attr(trim($valuee)) . '" selected >' . esc_html(get_the_title($valuee)) . '</option>';
				// 	}
				// }




				if ( 'products' == $to_be_sent['appliedon_upsell'] && is_array( $to_be_sent['procat_ids_upsell'] ) ) {

					foreach ( $to_be_sent['procat_ids_upsell'] as $key => $value ) {

						$product = wc_get_product( $value );

						if ( $product ) {
							if ( $product->is_type( 'variation' ) ) {

								$parent = wc_get_product( $product->get_parent_id() );
								$variation_attributes = wc_get_formatted_variation( $product, true );

								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {

								$title = $product->get_name();
							}

							echo '<option value="' . esc_attr( trim( $value ) ) . '" selected>' . esc_html( $title ) . '</option>';
						}
					}
				}




				$elt_product_category_html123='';
				$elt_parentid = get_queried_object_id();
				$elt_args = array(
					'numberposts' => -1,
					'taxonomy' => 'product_cat',
				);
				$elt_terms = get_terms($elt_args);

				if ( $elt_terms ) {   
					foreach ( $elt_terms as $elt_term1 ) {
						$selected123 = '';

						if ('categories'==$to_be_sent['appliedon_upsell'] && is_array($to_be_sent['procat_ids_upsell']) && in_array($elt_term1->term_id , $to_be_sent['procat_ids_upsell'])) {
							$selected123 = 'selected';
						}
						$elt_product_category_html123 = $elt_product_category_html123 . '<option class="ert_catopt" value="' . $elt_term1->term_id . '" ' . $selected123 . ' >' . $elt_term1->name . '</option>';
					}
				}

				?>


			</select>
		</td>
		<td id="upsell_21" style="display: none;">
			<select multiple id="upsell_funnel_select_category1" name="multi2[]" class="absbselect">
				<?php echo filter_var($elt_product_category_html123); ?>
			</select>
		</td>
	</tr>
</table>




<h2 style="text-align: left;">UpSell Products Display Location</h2>

<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

	<tr>
		<td style="width: 30%;">
			<strong>Select Location for Cart Page</strong>
		</td>

		<td style="width: 70%;">
			<select id="upsell_location_cart1" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
				<option value="woocommerce_before_cart"
				<?php
				if ('woocommerce_before_cart' == $to_be_sent['location_on_cart']) {
					echo 'selected';
				}
				?>
				>Before Add to Cart Form</option>
				<option value="woocommerce_after_cart"
				<?php
				if ('woocommerce_after_cart' == $to_be_sent['location_on_cart']) {
					echo 'selected';
				}
				?>
				>After Add to Cart Form</option>

			</select>
		</td>
	</tr>
	<tr>
		<td id="upsell_label_for_options1">
			<strong>Select Location for Checkout Page</strong>
		</td>
		<td>
			<select id="upsell_location_checkout1" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
				<option value="woocommerce_before_checkout_form"
				<?php
				if ('woocommerce_before_checkout_form' == $to_be_sent['location_on_checkout']) {
					echo 'selected';
				}
				?>
				>Before Checkout Form</option>
				<option value="woocommerce_after_checkout_form"
				<?php
				if ('woocommerce_after_checkout_form' == $to_be_sent['location_on_checkout']) {
					echo 'selected';
				}
				?>
				>After Checkout Form</option>

			</select>
		</td>

	</tr>
</table>

<style type="text/css">
	
	.table_ppp{
		border-collapse: collapse;
		width: 100%;
	}

	.table_ppp th, .table_ppp td {
		border: 1px solid #ccc;
		padding: 6px 10px;
		text-align: left;
	}
</style>

<script type="text/javascript">




	jQuery('.upcell_select_products1').select2({
		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss', 

				};
			},
			processResults: function( data ) {

				var options = [];
				if ( data ) {


					jQuery.each( data, function( index, text ) { 
						options.push( { id: text[0], text: text[1]  } );
					});

				}
				return {
					results: options
				};
			},
			cache: true
		},
		placeholder: 'Choose Products',
		minimumInputLength: 3 

	});

	jQuery('#upsell_funnel_select_product1').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss', 

				};
			},
			processResults: function( data ) {

				var options = [];
				if ( data ) {


					jQuery.each( data, function( index, text ) { 
						options.push( { id: text[0], text: text[1]  } );
					});

				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: true,
		placeholder: 'Choose Products',
		minimumInputLength: 3 

	});



	jQuery('#upsell_funnel_select_category1').select2();



	var absb_selected=jQuery('#absb_upsell_appliedon1').val();
	if ('products' == absb_selected) {
		jQuery('#upsell_label_for_options1').show();
		jQuery('#upsell_11').show();
		jQuery('#upsell_21').hide();
	}
	else if ('categories' == absb_selected) {
		jQuery('#upsell_label_for_options1').show();
		jQuery('#upsell_11').hide();
		jQuery('#upsell_21').show();    
	}



	jQuery('body').on('change', '#absb_upsell_appliedon1', function(){
		var absb_selected=jQuery('#absb_upsell_appliedon1').val();
		if ('products' == absb_selected) {
			jQuery('#upsell_label_for_options1').show();
			jQuery('#upsell_11').show();
			jQuery('#upsell_21').hide();
		}
		else if ('categories' == absb_selected) {
			jQuery('#upsell_label_for_options1').show();
			jQuery('#upsell_11').hide();
			jQuery('#upsell_21').show();    
		}
	})






</script>

	<?php


	wp_die();
}


function absb_ups_upsell_get_all_rules_from_db_fordatatable() {

	$upsell_rule_data=get_option('absb_rule_settings_upsell');

	if ( '' == $upsell_rule_data ) {
		$upsell_rule_data=array();
	}
	$return_json=array();




	foreach ($upsell_rule_data as $key => $value) {
		if ('true' == $value['activate_rule']) {
			$html='Active <i style="color:green;" class="fa fa-check" aria-hidden="true"></i>';
		} else {
			$html='Deactive <i style="color:red;" class="fa fa-remove"></i>';
		}

		if ('' == $value['upsell_rule_name']) {
			$rulename = 'Rule # ' . ( $key+1 );
		} else {
			$rulename = $value['upsell_rule_name'];
		}



		if ('products' == $value['appliedon_upsell']) {
			$appliedon = 'Products';
		} else if ('categories' == $value['appliedon_upsell'] ) {
			$appliedon = 'Categories';
		}


		$elt_row = array(
			'Serial #' => $key+1,
			'Rule Name' => $rulename,
			'Applied on' => $appliedon,               

			'Status' =>  $html,

			'Edit / Delete' => $key,
		);
		$return_json[] = $elt_row;

	}

	echo json_encode(array('data' => $return_json));
	wp_die();
}


function absb_saving_upsell_rule() {
	$upsell_rules_settingss=get_option('absb_rule_settings_upsell');

	if (''==$upsell_rules_settingss) {
		$upsell_rules_settingss=array();
	}

	$upsell_rules_settingss[]=$_REQUEST;
	update_option('absb_rule_settings_upsell', $upsell_rules_settingss);
	wp_die();
}


function absb_upsell_funnel_save_general_settings() {
	update_option('absb_saved_upsell_general_settings', $_REQUEST);
	wp_die();
}

function absb_grggggg_frq_bgt_search_productss_duplicate() {



	$absb_rule_settingsss=get_option('absb_frq_bgt_items');

	$selected_product_array = array();

	foreach ($absb_rule_settingsss as $key => $value) {

		$post_data = get_post_meta($value['selected_productzz'], 'selected_products', true);

		$selected_product_array[] = $value['selected_productzz'];

	}



	if (isset($_REQUEST['q'])) {
		$pro = sanitize_text_field( $_REQUEST['q'] );
	} else {
		$pro = '';
	}

	$data_array = array();
	$args       = array(
		'post_type' => array('product', 'product_variation'),
		'post_status' => 'publish',
		'numberposts' => -1,
		's' =>  $pro,

	);
	$pros       = get_posts($args);

	if ( !empty($pros)) {

		foreach ($pros as $proo) {
			$titel_already_exist = '';
			$is_disabled = false;

			if (is_array($selected_product_array) && in_array($proo->ID, $selected_product_array)) {
				$titel_already_exist = ' (Already Rule Applied)';
				$is_disabled = true;
			}
			$product=wc_get_product($proo->ID);
			if ('variable' != $product->get_type() && 'external' != $product->get_type()) {

				if ( 'variation' == $product->get_type() ) {
					$var_custom_attributes = $product->get_variation_attributes();
					if (count($var_custom_attributes) > 2) {
						$attribute = wc_get_formatted_variation( $var_custom_attributes, true );
						$title = $proo->post_title . ' ' . $attribute;						
					} else {
						$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
					}

				} else {
					$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				}

				// $title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				$data_array[] = array( $proo->ID, $title . $titel_already_exist, $is_disabled );
			}


		}
	}

	echo wp_json_encode( $data_array );

	die();
}


function absb_sftmskal_ajax_for_creating_select_for_template() {

	$data = get_option('saving_template_setting');
	?>
	<select id="select_template" style="width: 100% !important; margin-top: 3px !important; max-width: 55rem !important; padding: 6px;">
		<?php
		foreach ($data['temp_content'] as $key123 => $value123) {
			?>

			<option><?php echo esc_html($value123[0]); ?> </option>
			<?php
		}
		?>
	</select>
	<?php 

	wp_die();
}

function absb_frq_bgt_search_productss() {

	if ( isset( $_REQUEST['q'] ) ) {
		$pro = sanitize_text_field( $_REQUEST['q'] );
	} else {
		$pro = '';
	}

	$data_array = array();
	$args       = array(
		'post_type'      => array( 'product', 'product_variation' ),
		'post_status'    => 'publish',
		'numberposts'    => -1,
		's'              => $pro,
	);
	$pros = get_posts( $args );

	if ( ! empty( $pros ) ) {
		foreach ( $pros as $proo ) {
			$product = wc_get_product( $proo->ID );

			// Skip external products
			if ( 'external' === $product->get_type() ) {
				continue;
			}

			// Agar variation hai
			if ( $product->is_type( 'variation' ) ) {
				// Parent title
				$parent = wc_get_product( $product->get_parent_id() );
				$variation_attributes = wc_get_formatted_variation( $product, true ); // nicely formatted attributes

				$title = $parent->get_name() . ' - ' . $variation_attributes;

			} else {
				// Simple ya parent variable product
				$title = $proo->post_title;
			}

			// Length limit
			$title = ( mb_strlen( $title ) > 100 ) ? mb_substr( $title, 0, 99 ) . '...' : $title;

			$data_array[] = array( $proo->ID, $title );
		}
	}

	echo wp_json_encode( $data_array );

	wp_die();
}




function absb_sending_mail_to_all() {

	$bulk_main_array = array();
	if (isset($_REQUEST['email_to_all_subject']) && isset($_REQUEST['sendall_email'])) {
		$bulk_array = array( filter_var($_REQUEST['email_to_all_subject']), filter_var($_REQUEST['sendall_email'] ));
	}

	array_push($bulk_main_array, $bulk_array  );

	

	update_option('absb_bulk_email_content_subject', $bulk_main_array);

	if (! wp_next_scheduled ( 'sb_send_bulk_emails' )) {
		wp_schedule_event(time(), 'plug_3min', 'sb_send_bulk_emails');
	}

	wp_die();
} 

add_action( 'sb_send_bulk_emails', 'sb_send_bulk_emails_callback' );
// add_action( 'init', 'sb_send_bulk_emails_callback' );

function sb_send_bulk_emails_callback() {

	$bulk_email_data = get_option('absb_bulk_email_content_subject');

	$allusers = get_users( array(  'fields' => 'user_email', ) ) ;
	

	$curr_key = get_option('plugify_bulk_email_status');


	if ( 'email_sent' == $curr_key) {
		wp_clear_scheduled_hook('sb_send_bulk_emails'); 
		delete_option('plugify_bulk_email_status');
		return;
	}
	if (empty($curr_key) || '' ==  $curr_key  ) {
		$curr_key = 0;
	} else {
		if ('email_sent' != $curr_key) {
			$curr_key = ++$curr_key;
		} 
	}
	if ( $curr_key >= count($allusers) ) {
		update_option('plugify_bulk_email_status', 'email_sent');
		return;
	}

	$curr_key_length = $curr_key;
	if ($curr_key_length > count($allusers)) {
		$curr_key_length = count($allusers);
	}

	for ($key = $curr_key; $key < count($allusers); $key++) {

		$value = $allusers[$key];
		$to = $value;
		foreach ($bulk_email_data as $keyss => $valuess) {
			$subject = $valuess[0];
			$body = $valuess[1];
		}

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
		$headers .= 'From: ' . get_option('admin_email') . "\r\n";

		set_time_limit(20);
		sleep(2);
		$is_sent = wp_mail( $to, $subject, $body, $headers );
		if ($is_sent) {
			update_option('plugify_bulk_email_status', $key);
		}
	}

	$curr_key = get_option('plugify_bulk_email_status');

	if ( ( $curr_key + 1 ) >= count($allusers) ) {
		wp_clear_scheduled_hook('sb_send_bulk_emails'); 
		delete_option('plugify_bulk_email_status');
		return;
	}
}

function sb_cron_schedules( $schedules ) { 
	if (!isset($schedules['plug_3min'])) {
		$schedules['plug_3min'] = array(
			'interval' => 3*60,
			'display' => __('Once every 3 minutes'));
	}
	return $schedules;
}

add_filter('cron_schedules' , 'sb_cron_schedules');




function absb_sending_mail() {


	$template_data = get_option('saving_template_setting');



	$main_emails_array = array();
	if (isset($_REQUEST['send_mail_to'])) {

		$send_mail_to= filter_var($_REQUEST['send_mail_to']);
	}


	if ('products' == $send_mail_to && isset($_REQUEST['send_ids'])) {
		foreach (wc_clean( wp_unslash($_REQUEST['send_ids'])) as $key => $value) {
			$main_emails_array = array_merge($main_emails_array, absb_get_emailof_customers_for_products($value));
		}
		$main_emails_array = ( array_unique($main_emails_array ) );


	} else if ( 'user_roles' == $send_mail_to && isset($_REQUEST['send_ids']) ) {
		$main_emails_array = get_users( array( 'role__in' =>  wc_clean( wp_unslash($_REQUEST['send_ids'])), 'fields' => 'user_email', ) ) ;


	} else if ( 'users' == $send_mail_to && isset($_REQUEST['send_ids'])) {
		$main_emails_array = wc_clean( wp_unslash($_REQUEST['send_ids']));

	}



	foreach ($template_data['temp_content'] as $key123 => $value123) {
		if (isset($_REQUEST['email_template'])) {

			if ($_REQUEST['email_template'] == $value123[0]  ) {
				$mail_content = $value123[1];
				$mail_subject = $value123[2];
			} 

		}
	}


	$to = $main_emails_array;
	$subject = $mail_subject;
	$body = $mail_content;

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
	$headers .= 'From: abc@gmail.com' . "\r\n";

	add_filter('wp_mail_content_type', function( $content_type ) {
		return 'text/html';
	});

	wp_mail( $to, $subject, $body, $headers );

	wp_die();
}



function absb_get_emailof_customers_for_products( $product_id ) {
	global $wpdb;

	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {

		$customer_emails = $wpdb->get_col($wpdb->prepare("
			SELECT DISTINCT p.billing_email FROM {$wpdb->prefix}wc_orders AS p
			,{$wpdb->prefix}woocommerce_order_items AS i
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			WHERE p.status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-pending','wc-cancelled' )
			AND im.meta_key IN ( '_product_id', '_variation_id' )
			AND im.meta_value = %s
			", $product_id) );

	} else {
		$customer_emails = $wpdb->get_col($wpdb->prepare("
			SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			WHERE p.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-pending','wc-cancelled' )
			AND pm.meta_key IN ( '_billing_email' )
			AND im.meta_key IN ( '_product_id', '_variation_id' )
			AND im.meta_value = %s
			", $product_id) );
	}

	return $customer_emails;
}



function nfs_ajax_for_notification_show() {

	$gen_settings = get_option('nfs_general_settings_for_notify')  ;
	$message_settings = get_option('nfs_message_settings_for_notify') ;
	$time_settings =  get_option('nfs_time_settings_for_notify');
	$shortcode_settings = get_option('nfs_saving_shortcode_settings') ;
	$display_settings =  get_option('nfs_display_settings_for_notify');



	$first_notification_time = $time_settings['first_display']*1000;
	$notification_display_time = $time_settings['display_time']*1000;


	$virtualnames_array = explode( ',', $shortcode_settings['virtual_names'] );
	$virtualcity_array = explode( ',', $shortcode_settings['virtual_cities'] );
	$virtualtime_array = explode( ',', $shortcode_settings['virtual_timeago'] );
	$products_array = $shortcode_settings['procat_ids_notify'];

	$messages_array = $message_settings['notify_content'];

	$random_names = $virtualnames_array[rand(0, count($virtualnames_array)-1)];
	$random_city = $virtualcity_array[rand(0, count($virtualcity_array)-1)];
	$random_timeago = $virtualtime_array[rand(0, count($virtualtime_array)-1)];


	if (isset($shortcode_settings['appliedon_notify']) && 'products' == $shortcode_settings['appliedon_notify']) {
		$products_array = $shortcode_settings['procat_ids_notify'];
		$random_products = $products_array[rand(0, count($products_array)-1)];
	}

	if (isset($shortcode_settings['appliedon_notify']) && 'categories' == $shortcode_settings['appliedon_notify']) {    

		$args = array(      
			'post_type' => 'product',
			'post_status' => 'publish',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $shortcode_settings['procat_ids_notify'],
				),
			),
		);

		$products = get_posts($args);
		$random_products = $products[rand(0, count($products)-1)];
		$random_products = $random_products->ID;
	}

	$random_messages = $messages_array[rand(0, count($messages_array)-1)];
	$productss = wc_get_product( $random_products );

	if ( $productss ) {
		if ( $productss->is_type( 'variation' ) ) {
			$parent = wc_get_product( $productss->get_parent_id() );
			$variation_attributes = wc_get_formatted_variation( $productss, true );
			$names = $parent->get_name() . ' - ' . $variation_attributes;
		} else {
			$names = $productss->get_name();
		}

		$product_link = $productss->get_permalink();
		$link = '<a href="' . esc_url( $product_link ) . '">' . esc_html( $names ) . '</a>';
	}

	?>
	<div id="myModal" class="modalpopup1 animate__animated <?php echo esc_attr($display_settings['notify_animate']); ?>">

		<div class="modal-content"  style="display: inline-flex; width: 100%; ">


			<div style=" width: 20%; height: auto;" >

				<?php 
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $random_products ), 'single-post-thumbnail' );

				if (empty($image) || '' == $image[0]) { 
					$placeholder_image = get_option( 'woocommerce_placeholder_image' );
					$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
					$image=$image;
				} else {
					$image=$image;
				}
				?>

				<img src="<?php echo esc_attr($image[0]); ?>" width="100" height="60" >
			</div>


			<div style="width:283px; padding-left:15px; padding-right: 15px; " >


				<?php 

				if (str_contains($random_messages, '{first_name}')) { 

					$random_messages = str_replace('{first_name}', $random_names, $random_messages);
				}   
				if (str_contains($random_messages, '{city}')) { 

					$random_messages = str_replace('{city}', $random_city, $random_messages);
				}
				if (str_contains($random_messages, '{time_ago}')) { 

					$random_messages = str_replace('{time_ago}', $random_timeago, $random_messages);

				}   
				if (str_contains($random_messages, '{product}')) { 

					$random_messages = str_replace('{product}', $names, $random_messages);

				}

				if (str_contains($random_messages, '{product_with_link}')) { 

					$random_messages = str_replace('{product_with_link}', $link, $random_messages);

				}   

				// echo filter_var($random_messages);      
				do_action('plugify_allll_contenttt1', $random_messages);
				?>
			</div>
			<span class="close_notification" data-dismiss="modal">&times;</span>
		</div>
	</div>
	<?php

	wp_die();
}



add_action('plugify_allll_contenttt1', 'plugify_allll_contenttt1');

function plugify_allll_contenttt1 ( $random_messages ) {
	echo filter_var($random_messages);
}


function nfs_absbs_saving_mail_template_setting() {
	update_option('saving_template_setting', $_REQUEST);
	wp_die();
}


function nfs_saving_shortcode_settings_for_notify () {
	update_option('nfs_saving_shortcode_settings', $_REQUEST);
	wp_die();
}




function nfs_saving_message_settings_for_notify() {

	update_option('nfs_message_settings_for_notify', $_REQUEST);
	wp_die();
}


function nfs_saving_time_settings_for_notify() {

	update_option('nfs_time_settings_for_notify', $_REQUEST);
	wp_die();
}



function nfs_saving_display_settings_for_notify() {

	update_option('nfs_display_settings_for_notify', $_REQUEST);
	wp_die();
}


function nfs_saving_general_settings_for_notify() {

	update_option('nfs_general_settings_for_notify', $_REQUEST);
	wp_die();
}



function absb_qty_discount_plugin_activate_function () {

	$email_datum_for_price_negotiation = get_option('absb_ofer_email_contnets_datum');
	if ('' == $email_datum_for_price_negotiation) {
		$email_datum_for_price_negotiation = array(

			'ofr_recieved_mail_from'=>get_option('admin_email'),
			'ofr_recieved_mail_subject'=>'Request For Price Negotiation has been Received',
			'ofr_recieved_mail_content'=>'Thank you for your interest, Your request for price negotiation has been received. You will be notified shortly via E-mail',
			'ofr_accepted_subject'=>'Request for Price Negotiation has been accepted',
			'ofr_accepted_content'=>'Hi, We are pleased to inform you that your request for price negotiation against {product_name} is accepted at price {Approved_Discount}. and this discount is valid for {valid_till} hour(s) after this E-mail. Thank you',

			'ofr_rejeceted_subject'=>'Request for Price Negotiation has been Rejected ',
			'ofr_rejected_content'=>'Hi, We are really sorry your request for price negotiation against {product_name} has been rejected. Please try with some different amount. Thanks',
		);
		update_option('absb_ofer_email_contnets_datum', $email_datum_for_price_negotiation);
	}


	$template_data = get_option('saving_template_setting');

	if ('' == $template_data) {
		$template_data = array(
			'temp_content'=>array(
				array(
					'Template 01',
					'Hi Dear, We would like to thank you for being our customer! We have just received a new collection and would like to offer you a 50% discount. It will expire in 7 days. Do not miss out! Have a great day. Sincerely, The Store',
					'Discount Offer',
				)
			)
		);
		update_option('saving_template_setting', $template_data);
	}




	$gen_data_for_price_negotiate = get_option('absb_saved_general_settings_for_price_negotiate');


	if ('' == $gen_data_for_price_negotiate) {
		$gen_data_for_price_negotiate=array(
			'activate_offer'=>'true',
			'offer_show_onshop'=>'true',
			'view_ofr__btn_txt'=>'Offer Status',
			'create_offer_btn_bg_color'=>'black',
			'create_offer_btn_text_clr'=>'#fefefe',
			'view_offer_btn_bg_color'=>'black',
			'view_offer_btn_text_clr'=>'#fefefe',
			'popoup_head_txt'=>'Offer Price',

			'view_popup_headtxt'=>'Offer Status',
			'create_offer_btn_text'=>'Offer Your Price',


		);
		update_option('absb_saved_general_settings_for_price_negotiate', $gen_data_for_price_negotiate);
	}





	$upsell_gen_data = get_option('absb_saved_upsell_general_settings');

	if ('' == $upsell_gen_data) {
		$upsell_gen_data=array(
			'activateupsell'=>'true',
			'upsell_discription'=>'false',
			'upsell_hyperlink'=>'true',
			'upsell_dots'=>'true',
			'upsell_arrows'=>'true',
			'enable_autoplay'=>'true',
			'enable_loop'=>'true',
			'autoplayspeed'=>'2',
			'screens_for_500'=>'4',
			'screens_for_800'=>'4',
			'screens_for_1100'=>'4',
			'screens_for_1100_greater'=>'4',
			'upsell_title'=>'Recommended Products',


		);
		update_option('absb_saved_upsell_general_settings', $upsell_gen_data);
	}



	$gen_setting_for_quantity_Discnt=get_option('absb_gen_settings_for_quantity_discount');

	if ('' == $gen_setting_for_quantity_Discnt) {
		$gen_setting_for_quantity_Discnt=array(
			'location'=>'#beforeadd',
			'qty_dsct_activate'=>'true',
			'tabletitle'=>'Discount Ranges Table',
			'heading_1'=>'Quantity',
			'heading_2'=>'Discount',
			'heading_3'=>'New Price',
			'head_bg_color'=>'#ae7b3b',
			'head_text_color'=>'#ffffff',
			'table_bg_color'=>'#f5f5f5',
			'table_text_color'=>'#000000',


		);
		update_option('absb_gen_settings_for_quantity_discount', $gen_setting_for_quantity_Discnt);
	}

	$gen_setting_for_freq_bgt_products=get_option('frq_bgt_general_settings');

	if ('' == $gen_setting_for_freq_bgt_products) {
		$gen_setting_for_freq_bgt_products=array(
			'frq_bgt_activate'=>'true',
			'frq_bgt_image'=>'true',
			'frq_bgt_price'=>'true',
			'frq_bgt_cartbtn'=>'true',
			'frq_bgt_location'=>'beforeadding',
			'frq_bgt_tablename'=>'true',
			'frq_bgt_tabletitle'=>'Frequently Bought Products',
			'frq_bgt_enable_ad_cart'=>'true',


		);
		update_option('frq_bgt_general_settings', $gen_setting_for_freq_bgt_products);
	}


	$gen_settings_notify = get_option('nfs_general_settings_for_notify');
	if ('' == $gen_settings_notify) {
		$gen_settings_notify = array(
			'activatenotify'=>'false',
			'enablephone'=>'true',
			'notifyonshop'=>'true',
			'notifyonproduct'=>'true',
			'notifyoncart'=>'true',
			'notify_on_checkout'=>'true',
			'notify_on_custom_url'=>'false',
			'custom_url'=>'www.example.com',

		);
		update_option('nfs_general_settings_for_notify', $gen_settings_notify);
	}



	$display_settings_notify = get_option('nfs_display_settings_for_notify');
	if ('' == $display_settings_notify) {
		$display_settings_notify = array(
			'notify_bgcolor'=>'#E6EFF4',
			'notify_txt_color'=>'#070808',
			'notify_border_color'=>'#94D8E5',
			'notify_location'=>'leftbottom',
			'notify_animate'=>'animate__bounce',
			'notify_radius'=>'5',

		);
		update_option('nfs_display_settings_for_notify', $display_settings_notify);
	}



	$time_settings_notify = get_option('nfs_time_settings_for_notify');
	if ('' == $time_settings_notify) {
		$time_settings_notify = array(
			'number_of_notify'=>'25',
			'display_time'=>'10',
			'start_range_notify'=>'5',
			'end_range_notify'=>'10',

		);
		update_option('nfs_time_settings_for_notify', $time_settings_notify);
	}


	$message_settings_notify = get_option('nfs_message_settings_for_notify');
	if ('' == $message_settings_notify) {
		$message_settings_notify = array(
			'enable_custom_msg_for_product'=>'true',
			'min_for_custom'=>'200',
			'max_for_custom'=>'5',
			'notify_content'=>array('{first_name} in {city} purchased {product_with_link} {time_ago}'),

		);

		update_option('nfs_message_settings_for_notify', $message_settings_notify);
	}




	$shortcode_settings_notify = get_option('nfs_saving_shortcode_settings');
	if ('' == $shortcode_settings_notify) {



		$names = 'John,
		George,
		Musa,
		Arthur,
		Harry,
		Asha,
		Bryan,
		Julian,
		Kayden,
		Daniel,
		Tyler,
		William,
		Julia,
		Liana,
		Adams,
		David';



		$cities = 'Islamabad,
		Delhi (India),
		Sydney (Australia),
		Lahore (Pakistan),
		Toronto (Canada),
		California (USA),
		Tokyo (Japan),
		Jakarta,
		Mumbai,
		Rawalpindi,
		Dhaka,
		Queensland,
		Perth,
		Colombo';

		$timeago = 'Just Now,
		2 Minutes ago,
		5 Minutes ago ,
		10 Minutes ago ,
		15 Minutes ago ,
		30 Minutes ago,
		1 Hour ago,
		2 Hours ago,
		3 Hours ago,
		Last night,
		Yesterday';



		$shortcode_settings_notify = array(
			'virtual_names'=>trim(preg_replace('/\t+/', '', $names)),
			'virtual_cities'=>trim(preg_replace('/\t+/', '', $cities)),
			'virtual_timeago'=>trim(preg_replace('/\t+/', '', $timeago)),
		);
		update_option('nfs_saving_shortcode_settings', $shortcode_settings_notify);
	}
}


function frq_bgt_prevent_duplicate_value() {

	$absb_rule_settingsss=get_option('absb_frq_bgt_items');

	$selected_product_array = array();

	foreach ($absb_rule_settingsss as $key => $value) {

		$post_data = get_post_meta($value['selected_productzz'], 'selected_products', true);

		$selected_product_array[] = $value['selected_productzz'];

	}

	$absb_args = array(
		'posts_per_page' => '-1',
		'post_status'           => 'publish',
		'post_type'      =>   array('product', 'product_variation')
	);
	$absb_the_query = new WP_Query( $absb_args );
	$absb_product_options_html='';
	while ( $absb_the_query -> have_posts() ) {

		$absb_the_query -> the_post();   

		$absbproduct=wc_get_product(get_the_ID());


		if (is_array($selected_product_array) && in_array(get_the_ID(), $selected_product_array)) {
			$absb_product_options_html=$absb_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" disabled>' . get_the_title() . ' (Already Rule Applied)</option>';     
		} else {
			$absb_product_options_html=$absb_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '">' . get_the_title() . '</option>';     
		}



	}
	?>

	<select  id="to_prevent_duplicate" class="absbselect" >
		<option>Please select product</option>
		<?php echo filter_var($absb_product_options_html); ?>
	</select>


	<?php
	wp_die();
}




function frq_bgt_saving_general_settings() {

	update_option('frq_bgt_general_settings', $_REQUEST);
	wp_die();
}


function absb_update_edited_rules_settings_frq_bgt() {

	$allrules=get_option('absb_frq_bgt_items');

	if ( '' == $allrules ) {
		$allrules=array();
	}

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}

	$allrules[$index]=$_REQUEST;

	if (isset($_REQUEST['freq_bgt']) && isset($_REQUEST['selected_productzz'])) {

		$productsssssss = map_deep( wp_unslash( $_REQUEST['freq_bgt'] ), 'sanitize_text_field' );
		$productsss111 = map_deep( wp_unslash( $_REQUEST['selected_productzz'] ), 'sanitize_text_field' );
		update_post_meta($productsss111, 'selected_products', $productsssssss );
		update_option('absb_frq_bgt_items' , $allrules);
	}


	wp_die();
}



function absb_edit_frq_bgt() {


	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}
	$absb_all_rules_frq_items=get_option('absb_frq_bgt_items');

	$sendingdata=$absb_all_rules_frq_items[$index];
	$frq_bgt_saved_post_data = get_post_meta($sendingdata['selected_productzz'], 'selected_products', true);
	$absb_args = array(
		'posts_per_page' => '-1',
		'post_status'           => 'publish',
		'post_type'      =>   array('product', 'product_variation')
	);
	$absb_the_query = new WP_Query( $absb_args );
	$absb_product_options_html='';
	$absb_fb_product_options='';
	while ( $absb_the_query -> have_posts() ) {

		$absb_the_query -> the_post();   

		$absbproduct=wc_get_product(get_the_ID());


		if ('variable' != $absbproduct->get_type()) {
			$selected = '';
			$selected_fbp = '';
			if (is_array($frq_bgt_saved_post_data)) {

				if (in_array(get_the_ID(), $frq_bgt_saved_post_data)) {
					$selected_fbp = 'selected';
				}

			}


			if (get_the_ID() == $sendingdata['selected_productzz']) {
				$selected = 'selected';
			}

			if (get_the_ID() != $sendingdata['selected_productzz']) {

				$absb_fb_product_options=$absb_fb_product_options . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" ' . $selected_fbp . ' >' . get_the_title() . '</option>';
			}
			$absb_product_options_html=$absb_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" ' . $selected . ' >' . get_the_title() . '</option>';
		}

	}


	?>


	<div>
		<table class="absb_rule_tables" style="width: 90% !important; margin-left: 4% !important; border-bottom: none !important; border-radius: 0px !important;" id="tb111">
			<tr>
				<td style="width: 30%;">
					<strong>Selected Product <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> The product where frequently bought products will be shown</span>
							</div></strong>
				</td>
				<td style="width: 70%;">
					<select id="absb_select_product_frequently_1" class="absbselect" disabled="">
						<?php
						$product_id = $sendingdata['selected_productzz'];
						$product    = wc_get_product( $product_id );

						if ( $product ) {
							if ( $product->is_type( 'variation' ) ) {
								$parent = wc_get_product( $product->get_parent_id() );
								$variation_attributes = wc_get_formatted_variation( $product, true );
								$title = $parent->get_name() . ' - ' . $variation_attributes;
							} else {
								$title = $product->get_name();
							}

							echo '<option value="' . esc_attr( $product_id ) . '" selected>' . esc_html( $title ) . '</option>';
						}
						?>
					</select>
				</td>

			</tr>
		</table>



	</div>
	<div id="absb_divhiddenss" >
		<table class="absb_rule_tables" style="width: 90% !important; margin-left: 4% !important; border-top: none !important; border-radius: 0px !important;" id="tb222">
			<tr>
				<td style="width: 30%;">
					<strong>Frequently Bought Products <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
				</td>
				<td style="width: 70%;">
					<select multiple id="frq_bgt_products_1" style="width: 100%;">
						<?php
						if ( ! empty( $sendingdata['freq_bgt'] ) && is_array( $sendingdata['freq_bgt'] ) ) {
							foreach ( $sendingdata['freq_bgt'] as $key123 => $value123 ) {
								$product = wc_get_product( $value123 );

								if ( $product ) {
									if ( $product->is_type( 'variation' ) ) {
										$parent = wc_get_product( $product->get_parent_id() );
										$variation_attributes = wc_get_formatted_variation( $product, true );
										$title = $parent->get_name() . ' - ' . $variation_attributes;
									} else {
										$title = $product->get_name();
									}

									echo '<option value="' . esc_attr( trim( $value123 ) ) . '" selected>' . esc_html( $title ) . '</option>';
								}
							}
						}
						?>
					</select>
				</td>

			</tr>
		</table>
	</div>


	<script type="text/javascript">


		jQuery('#absb_select_product_frequently_1').select2({

			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					return {
						q: params.term, 
						action: 'frq_bgt_search_productss_duplicate', 

					};
				},
				processResults: function( data ) {
					console.log(data);
					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1], disabled: text[2]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			placeholder: 'Choose Products',
			minimumInputLength: 3,
			maximumSelectionLength: 6


		});



		jQuery('#frq_bgt_products_1').select2({

			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					var main_product = jQuery('#absb_select_product_frequently_1').val()

					return {
						q: params.term, 
						main_product:main_product,
						action: 'frq_bgt_search_productss_only_for_frq_bgt', 

					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			multiple: true,
			placeholder: 'Choose Products',
			minimumInputLength: 3,
			maximumSelectionLength: 6


		});



	</script>

	<style type="text/css">
		.absb_rule_tables {
			width: 100% !important;
			border: 1px solid lightgrey;
			border-radius: 4px;
			padding: 35px !important;
			margin: 5px;
		}
	</style>




	<?php
	wp_die();
}



function absb_get_all_rules_from_db_for_frq_bgt() {

	$absb_rule_settingsss=get_option('absb_frq_bgt_items');

	if ( '' == $absb_rule_settingsss ) {
		$absb_rule_settingsss=array();
	}
	$return_json=array();
	foreach ($absb_rule_settingsss as $key => $value) {
		$post_data = get_post_meta($value['selected_productzz'], 'selected_products', true);
		$freq_bought_products = '';
		foreach ($post_data as $key123 => $value123) {
			if ( 0 != $key123 ) {
				$freq_bought_products = $freq_bought_products . ',';
			}

			$product = wc_get_product($value123);

			if ( 'variation' == $product->get_type() ) {
				$var_custom_attributes = $product->get_variation_attributes();
				if (count($var_custom_attributes) > 2) {
					$attribute = wc_get_formatted_variation( $var_custom_attributes, true );
					$title = $product->get_name() . ' ' . $attribute;						
				} else {
					$title        = ( mb_strlen( $product->get_name() ) > 50 ) ? mb_substr( $product->get_name(), 0, 49 ) . '...' : $product->get_name();
				}

			} else {
				$title        = ( mb_strlen( $product->get_name() ) > 50 ) ? mb_substr( $product->get_name(), 0, 49 ) . '...' : $product->get_name();
			}

			$freq_bought_products = $freq_bought_products . $title;
		}


		$product_name_frq = wc_get_product($value['selected_productzz'])->get_name();

		$absb_row = array(
			'Serial No' => $key+1,

			'Applied On' => $product_name_frq,               

			'Frequently Bought' =>  $freq_bought_products,

			'Edit / Delete' => $key,
		);
		$return_json[] = $absb_row;

	}

	echo json_encode(array('data' => $return_json));
	wp_die();
}


function save_first_time_frq_bgt() {

	$frq_bgt_items=get_option('absb_frq_bgt_items');

	if (''==$frq_bgt_items) {
		$frq_bgt_items=array();
	}

	$frq_bgt_items[]=$_REQUEST;
	update_option('absb_frq_bgt_items', $frq_bgt_items);

	if (isset($_REQUEST['selected_productzz']) && isset($_REQUEST['freq_bgt'])) {

		$pros11 = map_deep( wp_unslash( $_REQUEST['selected_productzz'] ), 'sanitize_text_field' );

		$pros22 = map_deep( wp_unslash( $_REQUEST['freq_bgt'] ), 'sanitize_text_field' );


		update_post_meta($pros11, 'selected_products', $pros22);
	}

	wp_die();
}


function get_frequently_bought_products( $product_id  ) {
	$statuses = array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-cancelled' );


	if (isset($_REQUEST['selected_product'])) {
		$product_id = intval($_REQUEST['selected_product']);
	}

	$orders_ids = absb_get_orders_ids_by_product_id( $product_id, $statuses );
	$fbp_counts = array();
	foreach ($orders_ids as $key => $value) {
		$order = wc_get_order( $value );
		$items = $order->get_items();
		foreach ($items as $itm_key => $itm_val) {
			if ($product_id != $itm_val->get_product_id()) {
				if (isset($fbp_counts[$itm_val->get_product_id()])) {
					$fbp_counts[$itm_val->get_product_id()] += 1;
				} else {
					$fbp_counts[$itm_val->get_product_id()] = 1;
				}
			}
		}
	}
	arsort($fbp_counts);
	$final_array = array();
	$count = 0;
	foreach ($fbp_counts as $fin_key => $fin_value) {
		if ($count > 5) {
			break;
		}
		$final_array[] = $fin_key;
		++$count;
	}
	return ( $final_array );
}

function absb_get_orders_ids_by_product_id( $product_id, $order_status = array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-cancelled' )) {
	global $wpdb;


	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {

		$results = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT order_items.order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->prefix}wc_orders AS posts ON order_items.order_id = posts.id
			WHERE order_items.order_item_type = 'line_item'
			AND posts.status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-pending','wc-cancelled' )
			AND order_item_meta.meta_key = '_product_id'
			AND order_item_meta.meta_value = %s", $product_id));

	} else {

		$results = $wpdb->get_col($wpdb->prepare("SELECT order_items.order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			WHERE posts.post_type = 'shop_order'
			AND order_items.order_item_type = 'line_item'
			AND posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-pending','wc-cancelled' )
			AND order_item_meta.meta_key = '_product_id'
			AND order_item_meta.meta_value = %s", $product_id));

	}

	return $results;
}



function absb_frg_bgt_saving_product_frequently_bought() {


	$absb_rule_settingsss=get_option('absb_frq_bgt_items');
	foreach ($absb_rule_settingsss as $key => $value) {
		$post_data = get_post_meta($value['selected_productzz'], 'selected_products', true);
	}


	$absb_args = array(
		'posts_per_page' => '-1',
		'post_status'           => 'publish',
		'post_type'      =>   array('product', 'product_variation')
	);
	$absb_the_query = new WP_Query( $absb_args );
	$absb_product_options_html='';

	if (isset($_REQUEST['selected_product'])) {

		$pros12345 = map_deep( wp_unslash( $_REQUEST['selected_product'] ), 'sanitize_text_field' );


		$selected_products =get_frequently_bought_products($pros12345); 
	}

	while ( $absb_the_query -> have_posts() ) {

		$absb_the_query -> the_post();   

		$absbproduct=wc_get_product(get_the_ID());
		$selected = '';

		if (in_array(get_the_ID(), $selected_products)) {
			$selected = 'selected';
		}


		if (isset($_REQUEST['selected_product']) &&  get_the_ID() != $_REQUEST['selected_product']) {
			$absb_product_options_html=$absb_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" ' . $selected . '>' . get_the_title() . '</option>';
		}

	}

	?>
	<select id="select_for_products">
		<?php echo filter_var($absb_product_options_html); ?>
	</select>
	<?php

	wp_die();
}




function absb_deleting_rule_frq_bgt() {

	$indexnumber= ( isset( $_REQUEST['index'] ) ) ? filter_var($_REQUEST['index']) : '';
	$data=get_option('absb_frq_bgt_items');

	$rule = $data[$indexnumber];

	delete_post_meta($rule['selected_productzz'], 'selected_products');

	unset($data[$indexnumber]);
	$data=array_values($data);
	update_option('absb_frq_bgt_items', $data);
}


function absb_saving_first_rule_settings() {

	$rules_settingss=get_option('absb_rule_settings');

	if (''==$rules_settingss) {
		$rules_settingss=array();
	}

	$rules_settingss[]=$_REQUEST;
	update_option('absb_rule_settings', $rules_settingss);

	wp_die();
}

function absb_data_to_datatable() {


	$absb_rule_settings=get_option('absb_rule_settings');

	if ( '' == $absb_rule_settings ) {
		$absb_rule_settings=array();
	}
	$return_json=array();
	foreach ($absb_rule_settings as $key => $value) {
		if ('true' == $value['activaterule']) {
			$html='Active <i style="color:green;" class="fa fa-check" aria-hidden="true"></i>';
		} else {
			$html='Deactive <i style="color:red;" class="fa fa-remove"></i>';
		}
		if ('' == $value['appliedon'] ) {
			$writeit='shop';
		} else {
			$writeit=$value['appliedon'];
		}
		if ('' == $value['rulename']) {
			$rulename = 'Rule # ' . ( $key+1 );
		} else {
			$rulename = $value['rulename'];
		}
		$roles = $value['allowedrole'];
		if ('' == $value['allowedrole']) {
			$roles = 'All Roles';
		}
		$absb_row = array(
			'Rule Name' => $rulename,
			'Applied On' => $value['appliedon'],               

			'Status' =>  $html,

			'Allowed Role' =>  $roles,

			'Edit / Delete' => $key,
		);
		$return_json[] = $absb_row;

	}

	echo json_encode(array('data' => $return_json));
	wp_die();
}



function absb_delete_rule_functionss() {

	$indexnumber= ( isset( $_REQUEST['index'] ) ) ? filter_var($_REQUEST['index']) : '';
	$data=get_option('absb_rule_settings');
	unset($data[$indexnumber]);
	$data=array_values($data);
	update_option('absb_rule_settings', $data);

	wp_die();
}

function absb_edit_rules_div_function() {

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}
	$absb_all_rules=get_option('absb_rule_settings');

	$sendingdata=$absb_all_rules[$index];

	$absb_args = array(
		'posts_per_page' => '-1',
		'post_status'           => 'publish',
		'post_type'      =>   array('product', 'product_variation')
	);
	$absb_the_query = new WP_Query( $absb_args );
	$absb_product_options_html='';
	while ( $absb_the_query -> have_posts() ) {

		$absb_the_query -> the_post();   

		$absbproduct=wc_get_product(get_the_ID());
		if ('variable' != $absbproduct->get_type()) {



			if ('variable' != $absbproduct->get_type()) {
				$selected = '';

				if ('products'==$sendingdata['appliedon'] && is_array($sendingdata['procat_ids']) && in_array(get_the_ID(), $sendingdata['procat_ids'])) {
					$selected = 'selected';
				}

				$absb_product_options_html=$absb_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" ' . $selected . ' >' . get_the_title() . '</option>';
			}

		}
	}


	$absb_product_category_html='';
	$absb_parentid = get_queried_object_id();
	$absb_args = array(
		'numberposts' => -1,
		'taxonomy' => 'product_cat',
	);
	$absb_terms = get_terms($absb_args);
	if ( $absb_terms ) {   
		foreach ( $absb_terms as $absb_term1 ) {
			$selected = '';
			if ('categories'==$sendingdata['appliedon'] && is_array($sendingdata['procat_ids']) && in_array($absb_term1->term_id , $sendingdata['procat_ids'])) {
				$selected = 'selected';
			}

			$absb_product_category_html = $absb_product_category_html . '<option class="absb_catopt" value="' . $absb_term1->term_id . '" ' . $selected . ' >' . $absb_term1->name . '</option>';

		}  
	}
	?>
	<h3>Basic Settings</h3>
	<table id="absb_rule_table_01a" class="absb_rule_tables" style="width: 100%;">
		<tr>
			<td style="text-align: left;"><strong>Rule Name</strong>

				<input type="text" name="absb_name" id="absb_rule_name1" class="absbmsgbox" style="width:70%;" value="<?php echo esc_attr(( '' == $sendingdata['rulename'] )) ? esc_attr( 'Rule # ' . ( $index+1 )) : esc_attr( $sendingdata['rulename'] ); ?> ">

			</td>
			<td style="text-align:right;"><strong>Activate Rule</strong>

				<label class="switch">
					<input type="checkbox" id="absb_active_rule1"
					<?php
					if ( 'true' == $sendingdata['activaterule']) {
						echo 'checked';
					}
					?>
					>
					<span class="slider"></span>
				</label>
			</td>
		</tr>
	</table>
	<h3>Select Products/Categories </h3>
	<table id="absb_rule_table_02a" class="absb_rule_tables">
		<tr>
			<td style="width: 30%;">
				<strong>Applied On</strong>
			</td>

			<td style="width: 70%;">
				<select name="absb_selectone" id="absb_appliedon1" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
					<option value="products"
					<?php
					if ('products'==$sendingdata['appliedon']) {
						echo 'selected';
					}
					?>
					>Specific Products</option>
					<option value="categories"
					<?php
					if ('categories'==$sendingdata['appliedon']) {
						echo 'selected';
					}
					?>
					>Specific Categories</option>

				</select>
			</td>
		</tr>
		<tr>
			<td id="absb_label_for_options11"  style="width: 30%;">
				<strong>Select Product/Category <span style="color:red;">*</span></strong>
			</td>
			<td id="absb_11"  style="width: 70%;" >
				<select multiple id="absb_select_product11" class="absbselect" name="multi[]">
					<?php
					if ( 'products' == $sendingdata['appliedon'] && is_array( $sendingdata['procat_ids'] ) ) {

						foreach ( $sendingdata['procat_ids'] as $key => $value ) {

							$product = wc_get_product( $value );

							if ( $product ) {
								if ( $product->is_type( 'variation' ) ) {

									$parent = wc_get_product( $product->get_parent_id() );
									$variation_attributes = wc_get_formatted_variation( $product, true );

									$title = $parent->get_name() . ' - ' . $variation_attributes;
								} else {

									$title = $product->get_name();
								}

								echo '<option value="' . esc_attr( trim( $value ) ) . '" selected>' . esc_html( $title ) . '</option>';
							}
						}
					}
					?>
				</select>

			</td>
			<td id="absb_21" style="display: none;">
				<select multiple id="absb_select_category11" name="multi2[]" class="absbselect">
					<?php echo filter_var($absb_product_category_html); ?>
				</select>
			</td>
		</tr>
	</table>
	<h3>Discount Settings</h3>

	<div class="absb_rule_tables" style="padding-right: unset !important; width: 96% !important;">
		<button type="button" id="addranges1" style="background-color: green; color: white; border:1px solid green; padding: 8px 6px; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: 3px; float: right; margin-right: 20px; margin-bottom: 5px;"> Add Range</button>
		<table id="absb_rule_table_03a" class="" style="max-width: 98% !important; width: 98% !important;" >




<!-- 	<table id="absb_rule_table_03a" class="absb_rule_tables">
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td style="text-align: center;">
				<button type="button" id="addranges1" style="background-color: white; color: #007cba; border:1px solid #007cba; padding: 8px 6px; font-size: 14px; font-weight: 500; cursor: pointer; border-radius:4px;"><i class="fa-solid fa-plus"></i> Add Range</button>
			</td>
		</tr> -->
		<tr>
			<th style="width: 20%;">Start Range <span style="color:red;">*</span></th>
			<th style="width: 20%;">End Range <span style="color:red;">*</span></th>
			<th style="width: 20%;">Discount type <span style="color:red;">*</span></th>
			<th style="width: 20%;">Amount <span style="color:red;">*</span></th>
			<th style="width: 20%;">Action</th>
		</tr>

		<?php 

		foreach ($sendingdata['startrange'] as $key => $value) {


			?>
			<tr>
				<td>
					<input type="number" name="start" id="startrange1" class="starting1" value="<?php echo esc_attr($value); ?>" style="width: 100%; min-width: 100px;" >
				</td>
				<td>
					<input type="number" name="end" id="endrange1" class="ending1" value="<?php echo esc_attr($sendingdata['endrange'][$key]); ?>" style="width: 100%; min-width: 100px;" >

				</td>
				<td>
					<select id="discounttype1" class="distype1" style="width: 100%; min-width: 100px;" >
						<option value="fix"
						<?php
						if ('fix'== $sendingdata['discounttype'][$key]) {
							echo 'selected';
						}
						?>
						>Fixed</option> 
						<option value="per" 
						<?php
						if ('per'== $sendingdata['discounttype'][$key]) {
							echo 'selected';
						}
						?>
						>Percentage</option>
						<option value="revised" 
						<?php
						if ('revised'== $sendingdata['discounttype'][$key]) {
							echo 'selected';
						}
						?>
						>Revised Price</option>
					</select>
				</td>
				<td>
					<input type="number" name="amount" id="disamount1" class="discountamount1" value="<?php echo esc_attr($sendingdata['discountamount'][$key]); ?>"style="width: 100%; min-width: 100px;">
				</td>


				<td>
					<?php
					if ( 0 != $key ) {
						?>
						<button type="button" class="del" style="margin-left: 35%; border:1px solid red; padding:8px 10px; background-color:white; cursor:pointer; color: red; border-radius:4px;"><i class="fa fa-trash"></i></button>
						<?php
					}
					?>
				</td>


			</tr>
			<?php

		} 


		?>






	</table>
</div>
<table id="absb_rule_table_04a" class="absb_rule_tables">
	<h3>Roles Settings</h3>


	<tr>
		<td>
			<strong >Allowed Roles</strong>
		</td>
		<td>


			<?php 
			global $wp_roles;
			$absb_all_roles = $wp_roles->get_names();

			?>
			<select class="absb_customer_roleclass" id="absb_customer_role1" multiple="multiple" class="form-control " style="width: 98%;">
				<?php
				foreach ($absb_all_roles as $key_role => $value_role) {
					?>
					<option value="<?php echo filter_var($key_role); ?>"
						<?php

						if ('' == $sendingdata['allowedrole']) {

							echo 'All Roles';
						} else if (isset($sendingdata['allowedrole']) && in_array($key_role, $sendingdata['allowedrole'])) {
							echo 'selected';
						}
						?>
						>
						<?php echo filter_var(ucfirst($value_role)); ?>

					</option>
					<?php
				}

				?>

			</select>   
			<br><i style="color: #007cba;">(Important : Leaving empty will be considered as All Roles Allowed.)</i>

		</td>       
	</tr>       
	<tr>
		<td>
			<strong>Allow Guest User</strong>
		</td>
		<td>
			<label class="switch">
				<input type="checkbox" id="absb_qty_dict_is_guest1"
				<?php
				if ( 'true' == $sendingdata['absb_qty_dict_is_guest']) {
					echo 'checked';
				}
				?>
				>
				<span class="slider"></span>
			</label>
		</td>
	</tr>


</table>
<script type="text/javascript">

	jQuery('#absb_select_product11').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss', 

				};
			},
			processResults: function( data ) {

				var options = [];
				if ( data ) {


					jQuery.each( data, function( index, text ) { 
						options.push( { id: text[0], text: text[1]  } );
					});

				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: true,
		placeholder: 'Choose Products',
		minimumInputLength: 3 

	});


	jQuery('#absb_select_category11').select2(); 
	jQuery('#absb_customer_role1').select2(); 

	jQuery('body').on('click', '.del' , function() {
		jQuery(this).parent().parent().remove();

	});



</script>


<style type="text/css">




	#absb_rule_table_03a , #absb_rule_table_02a {
		border-collapse: collapse;
		width: 100%;
	}

	#absb_rule_table_03a th,
	#absb_rule_table_03a td, #absb_rule_table_02a th, #absb_rule_table_02a td {
		border: 1px solid #ccc;
		padding: 6px 10px;
		text-align: left;
	}
	


	@media screen and (max-width: 997px) {


		.modalpopup {
			overflow-y:scroll !important;
			overflow: auto !important;
			display: none; 
			position: absolute !important;
			z-index: 9999 !important;		
			left: 0;
			top: 0;
			width: 100% !important; 
			height: 100% !important; 
			overflow: auto !important; 
			/*background-color: rgb(0,0,0); */
			background-color: transparent !important; 
			padding: 3%; 
			margin-top: 6% !important !important;

		}

		.modal-content {
			background-color: #fefefe !important;
			margin: auto !important;
			padding: 20px !important;
			width: 80% !important;
			border-radius: 4px !important;
		}

		.close {
			color: #aaaaaa;
			float: right;
			/*font-size: 28px;*/
			/*font-weight: bold;*/
			border:none;
			background-color: white;
		}
		.close:hover,
		.close:focus {
			color: #000;
			text-decoration: none;
			cursor: pointer;
			background-color: white;
			margin: 0;
			color: red;
		}
		/*width: 50%;*/



		#absb_rule_table_03a, #absb_rule_table_02a {
			/*width: 50%;*/
			/*border:none !important;*/
			/*overflow: scroll;*/
			/*display: block;*/
		}
		#addranges1 {
			/*font-size: 9px !important;*/
			/*width: 168% !important;*/
		}
		#discounttype1 {
			/*min-width: 50% !important;*/
			/*width: unset !important;*/
		}
	}

</style>


	<?php

	wp_die();
}


function absb_update_edited_rules_settings_function() {

	$allrules=get_option('absb_rule_settings');

	if ( '' == $allrules ) {
		$allrules=array();
	}

	if (isset($_REQUEST['index']) ) {
		$index = sanitize_text_field($_REQUEST['index']);
	}


	$allrules[$index]=$_REQUEST;

	update_option('absb_rule_settings', $allrules);
	wp_die();
}



function absb_save_general_settings_quantity_discount() {

	update_option('absb_gen_settings_for_quantity_discount', $_REQUEST);
	wp_die();
}



add_action('init', 'plugify_absb_rewrite_endpointsss'); 

function plugify_absb_rewrite_endpointsss () {

	add_rewrite_endpoint( 'premium-support', EP_ROOT | EP_PAGES );

	$offer_rulesss = get_option('absb_offer_rule_settings');
	$count = 0;

	if (is_array($offer_rulesss)) {
		$count = count($offer_rulesss);
	}
	
	if ( $count > 0 ) {
		flush_rewrite_rules();
	}

}
