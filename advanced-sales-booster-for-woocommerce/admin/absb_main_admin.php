<?php 

class ABSB_Main_Class_Admin {
	public function __construct() {
		add_action( 'woocommerce_settings_absb', array( $this, 'absb_callback_against_mainfunctionings' ) );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'absb_filter_woocommerce_settings_tabs' ), 50);
		add_action( 'admin_enqueue_scripts', array( $this, 'absb_scripts_on_load1' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'frq_bgt_custom_product_tab' ), 50);

		add_action( 'woocommerce_product_data_panels', array( $this, 'frq_bgt_funs' ) );
		add_action( 'save_post', array( $this, 'absb_save_post_callback' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'absb_manage_col_data' ), 10, 2);
		add_filter( 'manage_posts_columns', array( $this, 'absb_manage_col_heads' ) );

		add_filter( 'post_row_actions', array( $this, 'absbs_mof__remove_row_actionsmao' ), 10, 1 );
	}

	public function absbs_mof__remove_row_actionsmao( $actions ) {
		if (isset($_GET['post_type']) && 'absb_custom_offers' == $_GET['post_type']) {

			unset( $actions['view'] );

			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}


	public function absb_manage_col_heads( $defaults ) {

		if (isset($_GET['post_type'])) {
			$post_type=sanitize_text_field($_GET['post_type']);
		}
		if ('absb_custom_offers' == $post_type) {

			$defaults['productt']='Product';
			$defaults['pprice']='Product Price';
			$defaults['Offeredprice']='Requested Price';
			$defaults['Oferdqty']='Desired Quantity';
			$defaults['statuss']='Status';
			return $defaults;
		}
		return $defaults;
	}

	public function absb_manage_col_data( $column_name, $post_id ) {

		if (isset($_GET['post_type'])) {
			$post_type=sanitize_text_field($_GET['post_type']);
		}

		if ('absb_custom_offers' == $post_type) {
			if ('productt' == $column_name) {
				
				$productoobligation=wc_get_product(get_post_meta($post_id, 'product_id', true));
				if (!is_a( $productoobligation, 'WC_Product' )) {
					return;
				}
				$productoobligation=wc_get_product(get_post_meta($post_id, 'product_id', true));
				if ('variation' == $productoobligation->get_type()) {

					$plogid=$productoobligation->get_parent_id();
				} else {
					$plogid=get_post_meta($post_id, 'product_id', true);
				}
				?>
				<a target="_blank" href="<?php echo filter_var(admin_url()); ?>post.php?post=<?php echo filter_var($plogid); ?>&action=edit"><span> <?php echo filter_var(get_the_title(get_post_meta($post_id, 'product_id', true)) ); ?> </span></a>  
				<?php
				
			}
			if ('pprice' == $column_name) {

				$pricee=get_post_meta(get_post_meta($post_id, 'product_id', true), '_sale_price', true);
				if ('' == $pricee || 0 == $pricee) {
					$pricee=get_post_meta(get_post_meta($post_id, 'product_id', true), '_regular_price', true);
				}  
				echo filter_var(wc_price($pricee));
			}
			if ('Offeredprice' == $column_name) {
				echo filter_var(wc_price(get_post_meta($post_id, 'uprice', true)));
			}
			if ('Oferdqty' == $column_name) {  
				echo filter_var( get_post_meta($post_id, 'qty', true) );
			}
			

			if ('statuss' == $column_name) {

				if ('pending' == get_post_meta($post_id, 'status', true)) {
					$bgclr='lightgrey';
					$clr='black';
					
				} else if ('rejected' == get_post_meta($post_id, 'status', true)) {
					$bgclr='red';	
					$clr='white';
				} else {
					$bgclr='green';

					$clr='white';
				}

				if (get_post_meta($post_id, 'status', true) == 'pending') {
					$write = 'Pending';
				} else if (get_post_meta($post_id, 'status', true) == 'rejected') {
					$write = 'Rejected';
				} else if (get_post_meta($post_id, 'status', true) == 'accepted') {
					$write = 'Accepted';
				}



				echo filter_var('<span style="margin-top:5%;border-radius:3px;padding:6px 9px;background-color:' . $bgclr . ';color:' . $clr . '">' . $write . '</span>');
				echo filter_var('<style>#applied_on{width:28% !important;}#date{width:25% !important;}</style>');
				?>
				<?php
			}
		}
	}
	
	public function absb_filter_woocommerce_settings_tabs ( $tabs ) {
		$tabs['absb'] = __('Sales Booster', 'absb');
		return $tabs;
	}


	public function absb_scripts_on_load1() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated 
		if ( isset($_GET['page']) && 'wc-settings' == $_GET['page'] && isset($_GET['tab']) && 'absb' == $_GET['tab'] ) {
			wp_enqueue_script('elt_datatables12', plugins_url( 'datatables.min.js', __FILE__ ), array('jquery'), '1.0', 'all' );
			wp_enqueue_style('elt_datatables2', 'https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.css', '1.0', 'all');
			wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', false, '1.0', 'all');
			wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all' );
			wp_enqueue_style( 'absbfontawsome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css', false, '1.0', 'all' );


			?>
			<style type="text/css">
				.woocommerce-save-button {
					display: none !important;
				}
			</style>
			<?php
		}
	}


	public function frq_bgt_custom_product_tab( $default_tabs ) {
		$default_tabs['custom_tab'] = array(
			'label'   =>  __( 'Frequently Bought Products', 'woocommerce' ),
			'target'  =>  'frq_bgt_funs',
			'priority' => 50

		);
		return $default_tabs;
	}

	public function frq_bgt_funs() {
		$absb_all_rules_frq_items=get_post_meta(get_the_ID(), 'selected_products' , true);
		$absb_rule_settingsss=get_option('absb_frq_bgt_items');

		?>

		<div id="frq_bgt_funs">
			<div id="a1234">
				<table class="absb_rule_tables" style="width: 90% !important; margin-left: 4% !important; ">
					<tr>
						<td style="width: 30%;">
							<strong>Frequently Bought Products  <span style="color:red;">*</span></strong>
						</td>
						<td style="width: 70%;">
							<select multiple id="frq_bgt_products_123" style="width: 100%;" name="post_select123[]">

								<?php

								foreach ($absb_rule_settingsss as $keyss => $valuess) {

									if (get_the_ID() == $valuess['selected_productzz']) {

										foreach ($valuess['freq_bgt'] as $key => $value) {
											echo esc_html('<option value="' . trim($value) . '" selected >' . get_the_title($value) . '</option>');

										}
									}
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<script type="text/javascript">
			jQuery('#frq_bgt_products_123').select2({
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
				minimumInputLength: 3,
				maximumSelectionLength: 6
			});
			jQuery('body').on('click', '#create_rule_inner', function(){
				var selected_productzz = <?php echo filter_var( get_the_ID() ); ?>
				// return;
				var freq_bgt = jQuery('#frq_bgt_products_123').val();

				return;
				if(''== freq_bgt) {
					alert('Please select at least one product')
					return;
				}

				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

					type : 'post',
					data : {
						action : 'save_first_time_frq_bgt',
						selected_productzz:selected_productzz,
						freq_bgt:freq_bgt
					},
					success : function( response ) {

						jQuery('#absb_divhidden').hide();
						jQuery('#sabebtndivfrq').hide();
						jQuery.ajax({
							url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

							type : 'post',
							data : {
								action : 'frq_bgt_prevent_duplicate_value',
							},
							success : function( response ) {
								window.onbeforeunload = null;	
							}

						});
					}

				});
			})
		</script>
		<style type="text/css">
			#a1234 {
				display: grid;
			}
		</style>
		<?php
	}

	public function absb_save_post_callback( $post_id ) {


		if (get_post_type() != 'product') {
			return;
		}

		$ruledata = get_option('absb_frq_bgt_items');

		if ( empty($ruledata) || !is_array($ruledata)) {
			return;
		}

		foreach ($ruledata as $key => $value) {

			if (isset($value['selected_productzz']) && $value['selected_productzz'] == $post_id) {
				if (isset($_REQUEST['post_select123'])) {
					$ruledata[$key]['freq_bgt'] = map_deep( wp_unslash($_REQUEST['post_select123']), 'sanitize_text_field');
					$prdex = map_deep( wp_unslash( $_REQUEST['post_select123'] ), 'sanitize_text_field' );
					update_post_meta($post_id , 'selected_products', $prdex);
				}				
			}

		}

		update_option('absb_frq_bgt_items', $ruledata);
	}





	public function absb_callback_against_mainfunctionings() {


		?>
		<div id="absbsavemsg" style="display:none;">
		</div>
		<div id="absb_buttons_div">


			<button type="button" name="freq_bght" id="absb_frequently_bought" class="main_buttons absbactive" style="padding: 10px 37px;">Frequently Bought</button>

			<button type="button" name="qtybaseddiscount" id="absb_qtydis" class="main_buttons" style="padding: 10px 12px;">Product Discount</button>

			

			<button type="button" name="mailingoption" id="absb_mailingoption" class="main_buttons" style="padding: 10px 48px;">Custom Emails</button>

			<button type="button" name="upsellfunnel" id="absb_upsell_funnel" class="main_buttons" style="padding: 10px 40px;">Upsell Products</button>

			<button type="button" name="makeoffer" id="absb_offer_module" class="main_buttons" style="padding: 10px 40px;">Price Offer</button>

			<button type="button" name="notifyforsale" id="absb_notify_for_sales" class="main_buttons" style="padding: 10px 28px;">Sales Notification</button>



		</div>

		<div id="absb_parent_div" >



			<div id="absb_frequently_bought_div" style="display: none; background-color: white;">
				<?php 
				include ('absb_frequently_bought_html.php'); 
				?>
			</div>


			<div id="absb_qtydiscount_div" style="display: none; background-color: white;">
				<?php
				include ('absb_qtydiscount_html.php');
				?>
			</div>


			<div id="absb_notify_for_sales_div" style="display: none; background-color: white;">
				<?php
				include ('absb_notify_for_sales_html.php');
				?>
			</div>


			<div id="absb_mailingoption_div" style="display: none; background-color: white;">
				<?php
				include ('absb_mailingoption_html.php');
				?>
			</div>


			<div id="absb_upsell_div" style="display: none; background-color: white;">
				<?php
				include ('absb_upsell_funnel.php');
				?>
			</div>


			<div id="absb_offer_div" style="display: none; background-color: white;">
				<?php
				include ('absb_price_negotiation_html.php');
				?>
			</div>


		</div>

		<script type="text/javascript">



			jQuery('body').on('click', '.main_buttons' , function() {
				jQuery('.main_buttons').removeClass('absbactive');
				jQuery(this).addClass('absbactive');
				jQuery('.add_buttons').removeClass('activee');

			})
			jQuery(document).ready(function(){
				jQuery('#absb_frequently_bought_div').show();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_upsell_div').hide();
				jQuery('#absb_offer_div').hide();

			})


			jQuery('body').on('click', '#absb_frequently_bought' , function() {
				jQuery('#absb_frequently_bought_div').show();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_upsell_div').hide();


				// jQuery('#frq_bgt_rule_settings').addClass('absbactive');
				jQuery('#frq_bgt_rule_settings').addClass('abcactive');
				jQuery('#frq_bgt_gen_settings').removeClass('abcactive');

				jQuery('#xyzdiv').show();
				jQuery('#frq_bgt_general_settings').hide();
				jQuery('#absb_offer_div').hide();



			});
			jQuery('body').on('click', '#absb_qtydis' , function() {
				jQuery('#absb_frequently_bought_div').hide();
				jQuery('#absb_qtydiscount_div').show();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_upsell_div').hide();

				// jQuery('#qtydisrulesetting').addClass('absbactive');
				jQuery('#qtydisrulesetting').addClass('abcactive');
				jQuery('#qtydisgensetting').removeClass('abcactive');
				jQuery('#qty_rule_div').show();
				jQuery('#qty_gen_div').hide();
				jQuery('#absb_offer_div').hide();



			});
			jQuery('body').on('click', '#absb_notify_for_sales' , function() {
				jQuery('#absb_frequently_bought_div').hide();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').show();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_upsell_div').hide();
				jQuery('#absb_offer_div').hide();


			});
			jQuery('body').on('click', '#absb_mailingoption' , function() {
				jQuery('#absb_frequently_bought_div').hide();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_upsell_div').hide();
				jQuery('#absb_mailingoption_div').show();
				jQuery('#absb_offer_div').hide();


			});
			jQuery('body').on('click', '#absb_upsell_funnel' , function() {
				jQuery('#absb_frequently_bought_div').hide();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_upsell_div').show();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_offer_div').hide();


			});
			jQuery('body').on('click', '#absb_offer_module' , function() {
				jQuery('#absb_frequently_bought_div').hide();
				jQuery('#absb_qtydiscount_div').hide();
				jQuery('#absb_notify_for_sales_div').hide();
				jQuery('#absb_upsell_div').hide();
				jQuery('#absb_mailingoption_div').hide();
				jQuery('#absb_offer_div').show();


			});

		</script>




		<style type="text/css">
			<style>
			.main_buttons {		
				cursor: pointer;
				margin-left: 0%;
				color: black;	
				margin-top: 1%;
				border-style:none ;
				padding: 10px;
				font-weight: 600;
				margin-left: 1% ;
				border-radius: 6px;
				width: 10%;
				border-bottom: none;

			}

			.main_buttons{
				margin: unset !important;
				border-radius: unset !important;
				color: #3c4044 !important;
				border: 1px solid lightgrey;
				font-weight: 600;
				border-bottom: none;
				background: #dcdcde !important;

			}
			
			.absbactive {
				background-color: #f1f1f1 !important;
				color: #3c4044 !important;		
				padding: 10px 12px;
			}
			.activee {
				color: white !important;		
				padding: 6px 8px;
				border: none;
				font-size: 14px;
				background-color: #f1f1f1;

			}
			#absb_parent_div {
				width: 100%;
				border: solid 1px lightgrey;
				background-color: white;

			}
			.main_buttons:hover {
				background: #fff !important;
				color: #3c4044 !important;
				cursor: pointer;

			}
			.abcactive {
				background-color: #f0f0f1;
				color: #000;
				border: 4px solid #fff !important ;

				border-bottom: none!important;
				font-weight: 800 !important;
			}

			.table-striped>tbody>tr:nth-of-type(odd)>* {
				--bs-table-accent-bg: var(--bs-table-striped-bg);
				color: var(--bs-table-striped-color);
			}
		
			#absb_datatable_frq_bgt_wrapper, #absb_datatable_wrapper {
				width: 95%;
				margin-left: 2%;
				margin-bottom: 1%;
				margin-top: 1%;

			}
		</style>
		<?php
	}
}
new ABSB_Main_Class_Admin();
