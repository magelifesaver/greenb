<?php

$display_settings = get_option('plgfyga_save_display_settings_for_tables');
if (isset($display_settings['plgfyga_table_location']) && 'beforecart' == $display_settings['plgfyga_table_location'] ) {
	add_action('woocommerce_before_add_to_cart_form', 'plgfyga_display_grouped_attributes');
} 

if ( isset($display_settings['plgfyga_table_location']) && 'default' == $display_settings['plgfyga_table_location']) {
	add_filter( 'woocommerce_product_tabs', 'plugify_custom_additional_tab', 98 );
	function plugify_custom_additional_tab( $tabs ) {

		$enables_for_specific_product = get_post_meta(get_the_ID(), 'create_for_this_product', true);
		$data = get_post_meta(get_the_ID(), 'custom_saved_groups', true);

		if ('on' == $enables_for_specific_product && !empty($data)) {
			$tabs['additional_information']['callback'] = 'plgfyga_display_grouped_attributes';	
		} 

		return $tabs;
	}
}

function plgfyga_display_grouped_attributes () {
	$enables_for_specific_product = get_post_meta(get_the_ID(), 'create_for_this_product', true);
	$data = get_post_meta(get_the_ID(), 'custom_saved_groups', true);
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
			'plgfyga_tabs_txt_color'=>'#000000',
			'plgfyga_tab_table_body_color'=>'#ededed',
			'plgfyga_tabs_table_body_txt_color'=>'#000000',
			'plgfyga_top_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_accor_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_tab_table_body_color_odd'=>'#E6E6E6',
			'plgfyga_tabs_head_main_bg_color'=>'#E6E6E6',
			'plgfyga_tabs_bg_active'=>'#fefefe',
			'plgfyga_tabs_txt_active'=>'#000000',
			'plgfyga_view_attri'=>'View Attributes',
			'plgfyga_btn_bg_color'=>'#000000',
			'plgfyga_btn_txt_color'=>'#fefefe',
		);
	}
	$product=wc_get_product(get_the_ID());
	$innersub=[];

	if ('on' == $enables_for_specific_product ) {		

		if ('tophead' == $display_settings['plgfyga_table_style']) {
			?>
			<div >
				<?php
				foreach ($data as $key111 => $value111) {	
					$allowed_to_display = false;
					$value1 = get_post_meta($value111, 'selected_attributes', true);
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}
					if ($allowed_to_display) {
						?>
						<div style="">
							<div style="font-weight: bold; font-size: 20px; padding-left: 10px; margin: unset; background-color:<?php echo esc_attr($display_settings['plgfyga_top_head_bg_color']); ?> ; color: <?php echo esc_attr($display_settings['plgfyga_top_head_txt_color']); ?>; "><?php echo esc_html(get_the_title($value111)); ?></div>

							<table class="tophead_tbl" style=" color: <?php echo esc_attr($display_settings['plgfyga_top_body_text_color']); ?>; ">
								<?php
								foreach ($value1 as $key222 => $value222) {


									if ('' != $product->get_attribute( $value222 )) {

										$taxonomy_id = wc_attribute_taxonomy_id_by_name( $value222 );
										$taxonomy_name = wc_attribute_taxonomy_name_by_id( $taxonomy_id );
										$label_name = wc_attribute_label( $taxonomy_name );
										?>

										<tr>
											<td style="width: 30% !important; padding:10px;"><strong style=""><?php echo esc_html($label_name); ?></strong></td>
											<td  style="width: 70% !important; padding:10px;"><?php echo esc_html($product->get_attribute( $value222 )); ?></td>
										</tr>
										<?php
									}

								}
								?>
							</table>
						</div>


						<?php
					}
				}
				?>
			</div>


			<style type="text/css">
				.tophead_tbl:not( .has-background ) tbody tr:nth-child(2n) td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color_odd']); ?> !important;
				}

				.tophead_tbl:not( .has-background ) tbody td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?> !important;
				}

			</style>

			<?php
		} else if ('left' == $display_settings['plgfyga_table_style']) {

			?>
			<div style="" id="leftheadeee">
				<?php
				foreach ($data as $key111 => $value111) {		


					$allowed_to_display = false;
					$value1 = get_post_meta($value111, 'selected_attributes', true);
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}

					if ($allowed_to_display) {

						?>
						<div>
							<table class="plugify_left_design">
								<tr>
									<th style="font-weight: bold; font-size: 20px; vertical-align: middle; background-color:<?php echo esc_attr($display_settings['plgfyga_top_head_bg_color']); ?> ; color:<?php echo esc_attr($display_settings['plgfyga_top_head_txt_color']); ?>; width: 30%; ">
										<div style=" color:<?php echo esc_attr($display_settings['plgfyga_top_head_txt_color']); ?>;"><?php echo esc_html(get_the_title($value111)); ?></div>
									</th>
									<td style="width: 70%; padding: unset; background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?> ; ">
										<table class="plugify_lft_tbl" style="margin:unset; color: <?php echo esc_attr($display_settings['plgfyga_top_body_text_color']); ?>; ">
											<?php
											foreach ($value1 as $key222 => $value222) {
												if ('' != $product->get_attribute( $value222 )) {

													$taxonomy_id = wc_attribute_taxonomy_id_by_name( $value222 );
													$taxonomy_name = wc_attribute_taxonomy_name_by_id( $taxonomy_id );
													$label_name = wc_attribute_label( $taxonomy_name );

													?>
													<tr>
														<td style="width: 30% !important; padding:10px;" ><strong  style=""><?php echo esc_html($label_name); ?></strong></td>
														<td style="width: 70% !important; padding:10px;"><?php echo esc_html($product->get_attribute( $value222 )); ?></td>
													</tr>
													<?php		
												}		
											}
											?>
										</table>
									</td>
								</tr>
							</table>
							
						</div>
						<?php
					}
				}
				?>
			</div>

			
			<style type="text/css">

				.plugify_left_design:not( .has-background ) tbody tr:nth-child(2n) td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color_odd']); ?> !important;
				}

				.plugify_left_design:not( .has-background ) tbody tr:nth-child(2n) td, {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?> !important;
					padding-left: 0px !important;
					padding-right: 0px !important;
				}

				.plugify_left_design:not( .has-background ) tbody td {
					padding-right: 0;
					padding-left: 0;
				}
			</style>
			<?php
		} else if ('accordion' == $display_settings['plgfyga_table_style']) {
			?>
			<div id="plgfyaccordion-container" >
				<?php
				
				$counter = 0;
				foreach ($data as $key111 => $value111) {   

					if (0 == $counter) {
						$active_class = 'active';
						$display_style = 'display: block';
					} else {
						$active_class = '';
						$display_style = 'display: none';
					}
					$counter++;   


					$allowed_to_display = false;
					$value1 = get_post_meta($value111, 'selected_attributes', true);
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}
					if ($allowed_to_display) {
						$value1 = get_post_meta($value111, 'selected_attributes', true);	
						?>
						<div class="plgfyaccordion-item">
							<div class="plugifyaccordion-header <?php echo esc_attr($active_class); ?>" style="background-color:<?php echo esc_attr($display_settings['plgfyga_accor_head_bg_color']); ?> ; font-weight: bold; font-size: 20px; color: <?php echo esc_attr($display_settings['plgfyga_accor_head_txt_color']); ?>; "><?php echo esc_html(get_the_title($value111)); ?></div>
							<div class="plugifyaccordion-content" style="padding: 0; <?php echo esc_attr($display_style); ?>">
								<table class="plugify_accor_tbl" style="margin-bottom: unset !important; color: <?php echo esc_attr($display_settings['plgfyga_accor_body_txt_color']); ?>; ">
									<?php
									foreach ($value1 as $key222 => $value222) {
										if ('' != $product->get_attribute( $value222 )) {
											$taxonomy_id = wc_attribute_taxonomy_id_by_name( $value222 );
											$taxonomy_name = wc_attribute_taxonomy_name_by_id( $taxonomy_id );
											$label_name = wc_attribute_label( $taxonomy_name );											
											?>
											<tr>
												<td  style="width: 30% !important; padding:10px;"><strong style=""><?php echo esc_html($label_name); ?></strong></td>
												<td  style="width: 70% !important; padding:10px;"><?php echo esc_html($product->get_attribute( $value222 )); ?></td>
											</tr>
											<?php       
										}										
									}
									?>
								</table>
							</div>
							<hr style="margin:unset !important;">
						</div>
						<?php
					}
				}
				?>
			</div>

			<style type="text/css">
				#plgfyaccordion-container {
					border-top: none;
					padding: 10px;
				}

				.plgfyaccordion-item {
					border-top: none;
					margin-bottom: 2px;
				}

				.plugifyaccordion-header {
					cursor: pointer;
					padding: 10px;
					font-size: 16px;
				}

				.plugifyaccordion-header:after {
					content: '\2304';
					color: #777;
					font-weight: bold;
					float: right;
					margin-left: 5px;
				}

				.plugifyaccordion-content {
					padding: 10px;
					display: none;
				}
				.plugifyaccordion-header.active:after {
					content: "\2303";
				}

				.plugifyaccordion-content {
					display: none;
				}

				.plugify_accor_tbl:not( .has-background ) tbody td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_accor_body_bg_color']); ?> !important;
					padding-left: 10px !important;
					padding-right: 0 !important;
				}

				.plugify_accor_tbl:not( .has-background ) tbody tr:nth-child(2n) td, fieldset, fieldset legend {
					background-color:<?php echo esc_attr($display_settings['plgfyga_accor_body_bg_color_odd']); ?> !important;
					padding-left: 10px !important;
					padding-right: 0 !important;

				}

			</style>


			<?php

		} else if ( 'tabs' == $display_settings['plgfyga_table_style']) {

			

			?>
			<div id="plgfyga_tabs" class="plgfyga_tabs" style="border: 1px solid #eee; background-color: <?php echo esc_attr($display_settings['plgfyga_tabs_head_main_bg_color']); ?>" >

				<?php
				$allowed_to_display = false;
				foreach ($data as $key111 => $value111) {		
					$value1 = get_post_meta($value111, 'selected_attributes', true);
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}
				}
				if ($allowed_to_display) {
					?>
					<ul style="border-radius: 0px;">
						<?php
						foreach ($data as $key111 => $value111) {	



							$allowed_to_display1 = false;
							$value1 = get_post_meta($value111, 'selected_attributes', true);
							foreach ($value1 as $key222 => $value222) {
								if ('' != $product->get_attribute( $value222 )) {
									$allowed_to_display1 = true;
								}
							}

							if ($allowed_to_display1) {

								?>
								<li style="border: none; outline: none; font-size: 16px; font-weight: bold; width: 100%; max-width: 100%; background-color: <?php echo esc_attr($display_settings['plgfyga_tabs_bg_color']); ?>; color: <?php echo esc_attr($display_settings['plgfyga_tabs_txt_color']); ?>;">
									<a style="width: 100%; text-align: center; color: <?php echo esc_attr($display_settings['plgfyga_tabs_txt_color']); ?>; background: <?php echo esc_attr($display_settings['plgfyga_tabs_bg_color']); ?>; white-space: normal; word-wrap: break-word;" href="#plugify_tab-<?php echo esc_attr($key111); ?>">
										<?php echo esc_html(get_the_title($value111)); ?>
									</a>
								</li>

								<?php
							}
						}

						?>
					</ul>
					<?php
				}
				foreach ($data as $key111 => $value111) {		

					$value1 = get_post_meta($value111, 'selected_attributes', true);



					?>
					<div id="plugify_tab-<?php echo esc_attr($key111); ?>" style="padding: 0; ">
						<table class="plgfy_tbs_tbl" style="margin: 0; height: 100%; background-color:<?php echo esc_attr($display_settings['plgfyga_tab_table_body_color']); ?> ; color: <?php echo esc_attr($display_settings['plgfyga_tabs_table_body_txt_color']); ?>; ">
							<?php
							foreach ($value1 as $key222 => $value222) {
								if ('' != $product->get_attribute( $value222 )) {

									$taxonomy_id = wc_attribute_taxonomy_id_by_name( $value222 );
									$taxonomy_name = wc_attribute_taxonomy_name_by_id( $taxonomy_id );
									$label_name = wc_attribute_label( $taxonomy_name );

									?>
									<tr>
										<td  style="max-width: 30% !important;"><strong style=""><?php echo esc_html($label_name); ?></strong></td>
										<td  style="max-width: 70% !important;"><?php echo esc_html($product->get_attribute( $value222 )); ?></td>
									</tr>
									<?php			
								}	
							}
							?>
						</table>
					</div>
					<?php
					
				}
				?>
			</div>

			<script>
				jQuery(function() {
					// jQuery( "#plgfyga_tabs" ).tabs();
					// jQuery( "#plgfyga_tabs" ).css('display', 'grid');					

					jQuery( ".plgfyga_tabs" ).tabs();
					jQuery( ".plgfyga_tabs" ).css('display', 'grid');
				});
			</script>
		
			<style type="text/css">
			/*	#plgfyga_tabs {
					display: none;
					grid-template-columns: 200px 1fr;				
				}

				#plgfyga_tabs ul {
					grid-column: 1 / 2;
				}

				#plgfyga_tabs .ui-tabs-panel {
					grid-column: 2 / 3;
				}
*/

				.plgfyga_tabs {
					display: none;
					grid-template-columns: 200px 1fr;				
				}

				.plgfyga_tabs ul {
					grid-column: 1 / 2;
				}

				.plgfyga_tabs .ui-tabs-panel {
					grid-column: 2 / 3;
				}



				
		
			
				.plgfy_tbs_tbl:not( .has-background ) tbody tr:nth-child(2n) td, fieldset, fieldset legend {
					background-color:<?php echo esc_attr($display_settings['plgfyga_tab_table_body_color']); ?> !important;


				}

				.plgfy_tbs_tbl:not( .has-background ) tbody td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_tab_table_body_color_odd']); ?> !important;

				}


				.ui-state-active a {
					color: <?php echo esc_attr($display_settings['plgfyga_tabs_txt_active']); ?> !important; !important;
					background: <?php echo esc_attr($display_settings['plgfyga_tabs_bg_active']); ?> !important;
					outline: none !important;
					border-radius: 3px !important;
					width: 100% !important;
					font-weight: 600;
					
				}

				.ui-tabs .ui-tabs-nav {
					margin: 0;

				}


				.ui-tabs .ui-tabs-nav li {
					list-style: none;
					float: left;
					position: relative;
					top: 0;
					margin: 1px .2em 0 0;
					border-bottom-width: 0;
					padding: 0;
					white-space: nowrap;
				}
				.ui-tabs .ui-tabs-nav .ui-tabs-anchor {
					float: left;
					padding: .5em 1em;
					text-decoration: none;
				}
				.ui-tabs .ui-tabs-nav li.ui-tabs-active {
					margin-bottom: -1px;
					padding-bottom: 1px;
				}

				.ui-tabs-panel .ui-corner-bottom .ui-widget-content {
					background: black !important;
				}
				.ui-tabs-tab {
					background: #eee !important;
					color: red !important;
				}



			</style>


			<?php

		} else if ('grid' == $display_settings['plgfyga_table_style']) {

			?>
			<div id="grideee">
				<?php
				$i = 0; 
				foreach ($data as $key111 => $value111) {		
					
					$allowed_to_display = false;
					$value1 = get_post_meta($value111, 'selected_attributes', true);
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}

					if ($allowed_to_display) {

						if ( 0 == $i % 2) { 
							echo '<div style="display: flex;">';
						}
						?>
						<div style="flex: 1; margin-left: 2%; margin-right: 2%; ">
							<div style="padding-left: 10px; font-size: 20px; font-weight: bold; margin: unset;  background-color:<?php echo esc_attr($display_settings['plgfyga_top_head_bg_color']); ?> ; color: <?php echo esc_attr($display_settings['plgfyga_top_head_txt_color']); ?>;"><?php echo esc_html(get_the_title($value111)); ?></div>

							<table class="tophead_tbl" style=" color: <?php echo esc_attr($display_settings['plgfyga_top_body_text_color']); ?>; ">
								<?php
								foreach ($value1 as $key222 => $value222) {
									if ('' != $product->get_attribute( $value222 )) {
										$taxonomy_id = wc_attribute_taxonomy_id_by_name( $value222 );
										$taxonomy_name = wc_attribute_taxonomy_name_by_id( $taxonomy_id );
										$label_name = wc_attribute_label( $taxonomy_name );

										?>
										<tr>
											<td  style="width: 30% !important; padding: 10px;"><strong style=""><?php echo esc_html($label_name); ?></strong></td>
											<td  style="width: 70% !important; padding: 10px;"><?php echo esc_html($product->get_attribute( $value222 )); ?></td>
										</tr >
										<?php			
									}	
								}
								?>
							</table>
						</div>

						<?php
						$i++; 
						if ( 0 == $i % 2 ) { 
							echo '</div>';
						}
					}
				}

				if ( 1 == $i % 2 ) {
					echo '</div>';
				}
				?>
			</div>

			<style type="text/css">


				.tophead_tbl:not( .has-background ) tbody tr:nth-child(2n) td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color_odd']); ?> !important;

				}

				.tophead_tbl:not( .has-background ) tbody td {
					background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?> !important;


				}

			</style>

			<?php

		}
		?>

		<style type="text/css">
			.plugify_accor_tbl td {
				background-color:<?php echo esc_attr($display_settings['plgfyga_accor_body_bg_color']); ?> !important;

			}
			.plugify_lft_tbl td {
				background-color:<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?> !important;

			}

		</style>

		<?php
	}


	?>

	<style type="text/css">
		@media (max-width: 768px) {

			#plgfyga_tabs, #grideee, #leftheadeee {
				overflow-x: scroll !important;
			}
		}

		
	</style>

	<?php
}

add_action('wp_footer', 'plgfyga_enqueue_scripts_stylesss');

function plgfyga_enqueue_scripts_stylesss () {
	// wp_enqueue_script('scripudsfe342', 'https://code.jquery.com/jquery-3.6.0.min.js', '1.0', 'all');
	wp_enqueue_script('scripudsfe432', 'https://code.jquery.com/ui/1.13.0/jquery-ui.min.js', '1.0', 'all');
	wp_enqueue_style('scripudsfe43zxczxcz2', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', '1.0', 'all');

	?>
	<script type="text/javascript">

		jQuery(document).ready(function() {

			jQuery('body').on('click', '.plugifyaccordion-header',function() {
				if (jQuery(this).hasClass('active')) {
					jQuery(this).removeClass('active');
					jQuery(this).siblings('.plugifyaccordion-content').stop().slideUp();
				} else {
					jQuery('.plugifyaccordion-header').removeClass('active');
					jQuery('.plugifyaccordion-content').slideUp();
					jQuery(this).addClass('active');
					jQuery(this).siblings('.plugifyaccordion-content').stop().slideToggle();
				}
			});
		});
	</script>

	<?php
}




add_action('woocommerce_after_shop_loop_item', 'plgfy_gatr_after_shop');
function plgfy_gatr_after_shop () {

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
			'plgfyga_tabs_txt_color'=>'#000000',
			'plgfyga_tab_table_body_color'=>'#ededed',
			'plgfyga_tabs_table_body_txt_color'=>'#000000',
			'plgfyga_top_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_accor_body_bg_color_odd'=>'#E6E6E6',
			'plgfyga_tab_table_body_color_odd'=>'#E6E6E6',
			'plgfyga_tabs_head_main_bg_color'=>'#E6E6E6',
			'plgfyga_tabs_bg_active'=>'#fefefe',
			'plgfyga_tabs_txt_active'=>'#000000',
			'plgfyga_view_attri'=>'View Attributes',
			'plgfyga_btn_bg_color'=>'#000000',
			'plgfyga_btn_txt_color'=>'#fefefe',
		);
	}





	$enables_for_specific_product = get_post_meta(get_the_ID(), 'create_for_this_product', true);

	if ('on' == $enables_for_specific_product && 'true' == $display_settings['plgfyga_enab_on_shop'] ) {

		$data = get_post_meta(get_the_ID(), 'custom_saved_groups', true);
		$product = wc_get_product(get_the_ID());
		$allowed_to_display = false;
		if (is_array($data)) {
			foreach ($data as $key111 => $value111) {	
				$value1 = get_post_meta($value111, 'selected_attributes', true);
				if (is_array($value1)) {
					foreach ($value1 as $key222 => $value222) {
						if ('' != $product->get_attribute( $value222 )) {
							$allowed_to_display = true;
						}
					}
				}
			}			
		}
		if ($allowed_to_display) {
		
			?>
		<button type="button" class="primary-button plgfyga_openpopup" id="plgfyga_openpopup" style="background-color:<?php echo esc_attr($display_settings['plgfyga_btn_bg_color']); ?> ; margin: 3px; color:<?php echo esc_attr($display_settings['plgfyga_btn_txt_color']); ?> ;"><?php echo esc_html($display_settings['plgfyga_view_attri']); ?></button>



		<div class="plgfyga_popup_overlay" id="plgfyga_popup_overlay">
			<div class="plgfyga_popup">
				<div class="plgfyga_popup_header">
					<h3 style="color: #000000; font-weight: bold;">Product Attributes</h3>
					<button class="plgfyga_popup_close">&times;</button>
				</div>
				<div class="plgfyga_popup_content" style="overflow-y: auto; max-height: calc(100vh - 150px);">
					<?php plgfyga_display_grouped_attributes(); ?>
				</div>
			</div>
		</div>

		<style type="text/css">
			.plgfyga_popup_overlay {
				display: none;
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background-color: rgba(0, 0, 0, 0.5);
				z-index: 9999;
			}

			.plgfyga_popup {
				position: absolute;
				min-width: 50%;
				top: 52%;
				left: 50%;
				transform: translate(-50%, -50%);
				background-color: #fff;
				padding: 20px;
				border-radius: 5px;
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
			}

			.plgfyga_popup_header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}

			.plgfyga_popup_header h2 {
				font-size: 20px;
				margin: 0;
			}

			.plgfyga_popup_close {
				/*margin-right: 15px !important;*/
				/*float: right;*/
				font-size: 20px;
				/*font-weight: bold;*/
				opacity: 1 !important;
				background: unset !important;
				outline: none;

				position: absolute;
				top: 0;
				right: 0;

			}

			.plgfyga_popup_close:hover {
				color: #f00;
			}
			.plgfyga_popup_close:active {
				outline: none;
			}

			.plgfyga_popup_content {
				margin-top: 10px;
			}

			@media (max-width: 768px) {
				.plgfyga_popup {
					position: absolute !important;
					min-width: 50% !important;
					top: 50% !important;
					left: 50% !important;
					transform: translate(-50%, -50%) !important;
					background-color: #fff !important;
					padding: 20px !important;
					border-radius: 5px !important;
					box-shadow: 0 0 10px rgba(0, 0, 0, 0.5) !important;
					max-height: 80vh !important;
					overflow-y: scroll !important;
					width: 97% !important;
				}
			}

		</style>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('body').on('click', '#plgfyga_openpopup', function() {
					jQuery(this).parent().find('#plgfyga_popup_overlay').show();

				})

				jQuery('body').on('click', '.plgfyga_popup_close', function() {
					jQuery(this).parentsUntil('plgfyga_popup_overlay').find('#plgfyga_popup_overlay').hide();

				})
			})
		</script>
			<?php
		}
	}


}
