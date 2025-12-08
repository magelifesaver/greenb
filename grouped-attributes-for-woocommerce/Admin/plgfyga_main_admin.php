<?php

add_action( 'init', 'plgfyga_register_posttyoe' );
add_action('add_meta_boxes', 'plgfyga_creating_meta_box'); 
add_filter('woocommerce_settings_tabs_array', 'plgfyga_filter_woocommerce_settings_tabs', 50);
add_action('woocommerce_settings_plgfyga', 'plgfyga_callback_against_mainfunctionings');

function plgfyga_filter_woocommerce_settings_tabs ( $tabs ) {
	$tabs['plgfyga'] = 'Group Attributes';      
	return $tabs;
}

function plgfyga_callback_against_mainfunctionings () {
	include ('plgfyga_display_settings.php');
}

function plgfyga_register_posttyoe() {
	if (isset($_GET['post_type']) && 'plgfyga_grp_attr' == $_GET['post_type']) {
		wp_enqueue_script( 'select2da', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', false, '1.0', 'all');
		wp_enqueue_style( 'select2asdd', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all' );
	}
	if (isset($_GET['post'])) {
		if ('plgfyga_grp_attr' == get_post_type(sanitize_text_field($_GET['post']))) {
			wp_enqueue_script( 'select2da', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', false, '1.0', 'all');
			wp_enqueue_style( 'select2asdd', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all' );
		}
	}	

	register_post_type( 'plgfyga_grp_attr',
		array(
			'labels' => array(
				'name' => __( 'Group Attributes' ),
				'singular_name' => __( 'Group Attributes' ),
				'add_new' => __( 'Add New Group Attribute' ),
				'add_new_item' => __( 'Add New Group Attribute' ),
				'edit_item' => __( 'Edit Group Attribute' )
			),
			'public' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => 'Group Attributes'),
			'show_in_rest' => false,
			'menu_icon'   => 'dashicons-products',
			'show_in_menu'        => 'edit.php?post_type=product', 
			'supports' => array('title'),
		)
	);
}

function plgfyga_creating_meta_box () {
	add_meta_box( 'plgfyga_request_dets', 'Group Attributes', 'plgfyga_mof_meta_box_details', 'plgfyga_grp_attr', 'normal', 'low' );
}

function plgfyga_mof_meta_box_details () {
	$all_attributes   = wc_get_attribute_taxonomies();

	?>
	<div style="width: 100%;" id="plugify_diving">
		<table style="width: 100%;">
			<tr>
				<td style="width: 30%;"><strong>Attributes:</strong></td>
				<td  style="width: 70%;">
					<?php
					$selected_attributes = get_post_meta(get_the_ID(), 'selected_attributes', true);
					if (isset($selected_attributes) && is_array($selected_attributes)) {
						if (isset($all_attributes) && is_array($all_attributes)) {
							usort( $all_attributes, function( $a, $b ) use ( $selected_attributes ) {
								$posA = array_search($a->attribute_name, $selected_attributes);
								$posB = array_search($b->attribute_name, $selected_attributes);
								return $posA - $posB;
							});
						}
					}
					
					?>
					<select multiple="multiple" name="select_attributes[]" class="select_attributes" id="select_attributes" style="display: block; width: 100%;" required>
						<?php
						foreach ($all_attributes as $attribute) {
							$selected = '';

							if ( is_array($selected_attributes) && in_array($attribute->attribute_name, $selected_attributes)) {
								$selected = 'selected';
							}
							echo filter_var('<option value="' . esc_attr($attribute->attribute_name) . '" ' . $selected . '>' . esc_html($attribute->attribute_label) . '</option>');
						}
						?>
					</select>
				</td>
			</tr>
		</table>
	</div>
	<script>			
		jQuery(document).ready(function(){
			setTimeout(function(){
				jQuery("#select_attributes").select2({
					multiple: true,
					placeholder: 'Select or search attribute'
				}).on("select2:select", function (evt) {
					var id = evt.params.data.id;

					var element = jQuery(this).children("option[value="+id+"]");

					moveElementToEndOfParent(element);

					jQuery(this).trigger("change");
				});
				var ele=jQuery("#select_attributes").parent().find("ul.select2-selection__rendered");
				ele.sortable({
					containment: 'parent',
					update: function() {
						orderSortedValues();
					}
				});

				orderSortedValues = function() {
					var value = ''
					jQuery("#select_attributes").parent().find("ul.select2-selection__rendered").children("li[title]").each(function(i, obj){

						var element = jQuery("#select_attributes").children('option').filter(function () { return jQuery(this).html() == obj.title });
						moveElementToEndOfParent(element)
					});
				};
				moveElementToEndOfParent = function(element) {
					var parent = element.parent();
					element.detach();
					parent.append(element);
				};			

			},500);

		})
	</script>
	<style type="text/css">
		#plugify_diving .select2-selection__choice {
			background: #ebebeb !important;
			color: #262626 !important;
			width: 98% !important;
			padding: 2px 7px !important;
			height: 25px !important;
			line-height: 24px !important;
			cursor: grabbing !important;
		}
		#plugify_diving li.select2-selection__choice:after {
			content: "\f545";
			font-family: dashicons;
			display: inline-block;
			float: right;
			text-decoration: inherit;
			text-transform: none;
			text-rendering: auto;
			-webkit-font-smoothing: antialiased;
			transition: color .1s ease-in;
		}
	</style>
	<?php
}
function plgfyga_save_post_data( $post_id ) {

	if ( get_post_type( $post_id ) == 'plgfyga_grp_attr' && ! get_the_title( $post_id ) ) {
		$post_update = array(
			'ID'         => $post_id,
			'post_title' => 'Group Attribute ' . $post_id
		);
		wp_update_post( $post_update );
	}

	if ( isset( $_REQUEST['select_attributes'] ) ) {
		update_post_meta( $post_id, 'selected_attributes', array_map( 'sanitize_text_field', wp_unslash($_REQUEST['select_attributes']  )));	
	} else {
		update_post_meta( $post_id, 'selected_attributes', '');
	}
}
add_action( 'save_post_plgfyga_grp_attr', 'plgfyga_save_post_data' );
add_filter('manage_posts_columns', 'plgfyga_manage_col_heads');
function plgfyga_manage_col_heads ( $defaults ) {
	if (isset($_GET['post_type'])) { 
		$post_type=sanitize_text_field($_GET['post_type']);
	}	 			
	if ( 'plgfyga_grp_attr' == $post_type ) {
		$defaults['_Attributes']='Attributes';
		return $defaults;
	}
	return $defaults;
}
add_action('manage_posts_custom_column', 'plgfyga_manage_col_data', 10, 2);

function plgfyga_manage_col_data( $column_name, $post_id ) { 
	if (isset($_GET['post_type'])) {
		$post_type=sanitize_text_field($_GET['post_type']);
	}
	if ('plgfyga_grp_attr' == $post_type) { 

		if ('_Attributes' == $column_name) {
			$attributes = get_post_meta($post_id, 'selected_attributes', true);

			if (is_array($attributes)) {
				foreach ($attributes as $key => $value) {
					echo filter_var('<strong>' . $value . '</strong><br>');
				}
			}
		}
	}
}
function my_custom_product_attribute_field() {
	?>
	<div class="skldjk" style="margin-top: 3%; margin-left: 4%;">
		<input type="checkbox" id="create_for_this_product" name="create_for_this_product"
		<?php
		if ('on' == get_post_meta(get_the_ID(), 'create_for_this_product', true) ) {
			echo 'checked';
		}
		?>
		>
		<?php 
		$selected_valssss = get_post_meta(get_the_ID(), 'custom_saved_groups', true);
		?>

		<label>Create group for this product</label><br><br>
		<div id="plgyfyga_div_innersss" style="margin-bottom: 2%; width: 100%; display: inline-flex;">
			<div style="width: 50%;">
				<label id="label_for_sel" for="custom_saved_groups"><strong>Manage Attribute Groups</strong></label><br>
				<?php
				$args = array(
					'post_type' => 'plgfyga_grp_attr',
					'posts_per_page' => -1,
				);
				$posts = get_posts( $args );

				if (isset($posts) && is_array($posts)) {
					if (isset($selected_valssss) && is_array($selected_valssss)) {
						usort( $posts, function( $a, $b ) use ( $selected_valssss ) {
							$posA = array_search($a->ID, $selected_valssss);
							$posB = array_search($b->ID, $selected_valssss);
							return $posA - $posB;
						});					
					}
				}


				?>
				<select multiple="multiple" name="custom_saved_groups[]" id="custom_saved_groups" style="width: 80%; ">
					<?php
					foreach ( $posts as $post ) {
						$title = $post->post_title;
						$value = $post->ID;
						if ( is_array($selected_valssss) && in_array($value , $selected_valssss )  ) {
							echo filter_var('<option value="' . $value . '" selected>' . $title . '</option>');
						} else {
							echo filter_var('<option value="' . $value . '">' . $title . '</option>');
						}						
					}
					?>
				</select>

				<button class="button" type="button" id="my_custom_load_button">Load</button>
			</div>
			<div style="width: 50%;">
				<?php 

				$terms = get_terms( array(
					'taxonomy' => 'attr_taxonomy',
					'hide_empty' => false,
				) );

				?>
				<label for="custom_saved_groups_taxonomy"><strong>Load Attributes Categories</strong></label><br>			
				<select name="custom_saved_groups_taxonomy" id="custom_saved_groups_taxonomy" style="width: 80%;">
					<option value="" > Select Group Category </option>
					<?php
					foreach ( $terms as $term ) {
						echo filter_var('<option value="' . $term->term_id . '">' . $term->name . '</option>');
					}
					?>
				</select>

				<button class="button" type="button" id="my_custom_load_button_for_taxonomy" style="">Load</button>
			</div>


		</div>
	</div>


	<style type="text/css">
		#plgyfyga_div_innersss .select2-selection__choice {
			background: #ebebeb !important;
			color: #262626 !important;
			width: 96% !important;
			padding: 2px 7px !important;
			height: 25px !important;
			line-height: 24px !important;
			cursor: grabbing !important;
		}



		#plgyfyga_div_innersss li.select2-selection__choice:after {
			content: "\f545";
			font-family: dashicons;
			display: inline-block;
			float: right;
			text-decoration: inherit;
			text-transform: none;
			text-rendering: auto;
			-webkit-font-smoothing: antialiased;
			transition: color .1s ease-in;
		}

		#plgyfyga_div_innersss .select2-search__field {
			width: 100% !important;
		}

	</style>

	<script type="text/javascript">
		jQuery( document ).ready( function() {		



			jQuery('#custom_saved_groups').on('select2:unselect', function(e) {

				var unselected_group = e.params.data.id;
				var remaining_selected_groups = jQuery('#custom_saved_groups').val();

				jQuery.ajax({
					url : '<?php echo filter_var(admin_url() . 'admin-ajax.php'); ?>',

					type : 'post',
					data : {
						action : 'plgfyga_group_atributes_unload_one_by_one',				

						unselected_group : unselected_group,
						remaining_selected_groups:remaining_selected_groups

					},
					success : function( response ) {	


						var dataa = JSON.parse(response);
						console.log(dataa);
						if (confirm("Also delete the attributes of this group?")) {
							for (var i = 0; i < dataa.length; i++) {
								(function(i) {
									setTimeout(function() {
									// jQuery('.woocommerce_attribute.pa_'+dataa[i]+' a.remove_row').click();
										jQuery('.woocommerce_attribute.pa_'+dataa[i]).remove();

									}, 100 * i); 
								}(i));
							}
						}


					}
				});
				
			});




			setTimeout(function(){
				jQuery("#custom_saved_groups").select2({
					multiple: true,
					placeholder: 'Select or search attribute groups'
				}).on("select2:select", function (evt) {
					var id = evt.params.data.id;

					var element = jQuery(this).children("option[value="+id+"]");

					moveElementToEndOfParent(element);

					jQuery(this).trigger("change");
				});

				var ele=jQuery("#custom_saved_groups").parent().find("ul.select2-selection__rendered");
				ele.sortable({
					containment: 'parent',
					update: function() {
						orderSortedValues();
						
					}
				});

				orderSortedValues = function() {
					var value = ''
					jQuery("#custom_saved_groups").parent().find("ul.select2-selection__rendered").children("li[title]").each(function(i, obj){

						var element = jQuery("#custom_saved_groups").children('option').filter(function () { return jQuery(this).html() == obj.title });
						moveElementToEndOfParent(element)
					});
				};

				moveElementToEndOfParent = function(element) {
					var parent = element.parent();

					element.detach();

					parent.append(element);
				};
			},500);


			jQuery('body').on('change', '#create_for_this_product', function() {
				if (jQuery('#create_for_this_product').prop('checked')) {
					jQuery('#plgyfyga_div_innersss').show();

				} else {
					jQuery('#plgyfyga_div_innersss').hide();
				}
			})


			if (jQuery('#create_for_this_product').prop('checked')) {
				jQuery('#plgyfyga_div_innersss').show();
			} else {
				jQuery('#plgyfyga_div_innersss').hide();				

			}

			jQuery( '#my_custom_load_button_for_taxonomy' ).click( function() {

				var saved_taxonomy_id = jQuery('#custom_saved_groups_taxonomy').val()

				if (!saved_taxonomy_id) {
					alert('Please choose Attribute Group Taxanomy to add');
					return;
				}
				jQuery('#my_custom_load_button_for_taxonomy').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Loading');

				jQuery('#my_custom_load_button_for_taxonomy').prop('disabled', true);
				jQuery('body').css('cursor' , 'wait');


				jQuery.ajax({
					url : '<?php echo filter_var(admin_url() . 'admin-ajax.php'); ?>',

					type : 'post',
					data : {
						action : 'plgfyga_group_atributes_sorting_taxonomiessss',				

						saved_taxonomy_id : saved_taxonomy_id

					},
					success : function( response ) {							
						var dataa=JSON.parse(response);						

						var groups_ids = dataa.groups_ids;
						var groups_attributes = dataa.groups_attributes;

						var selected_vals =	jQuery('#custom_saved_groups').val();

						var mergedArray = jQuery.merge(groups_ids, selected_vals);

						jQuery('#custom_saved_groups').val(mergedArray).trigger('change');		

						jQuery('#my_custom_load_button_for_taxonomy').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Load');
						jQuery('#my_custom_load_button_for_taxonomy').prop('disabled', false);
						jQuery('body').css('cursor' , 'unset');


						var count = 0;
						for (var i = 0; i < groups_attributes.length; i++) {
							if (jQuery("[data-taxonomy=pa_"+groups_attributes[i]+"]").length == 0) {
								count = count + 1;
								
								(function(i){
									setTimeout(function(){
										if (jQuery("[data-taxonomy=pa_"+groups_attributes[i]+"]").length == 0) {
											add_attribute_to_list('pa_'+groups_attributes[i]);
										}
									}, 1000 * count);
								}(i));
							}

						}

					}
				});
			})

			jQuery( '#my_custom_load_button' ).click( function() {


				if (jQuery('#custom_saved_groups').val() == '') {
					alert('Please choose Attribute Groups to add');
					return;
				}

				jQuery('#my_custom_load_button').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Loading');

				jQuery('#my_custom_load_button').prop('disabled', true);
				jQuery('body').css('cursor' , 'wait');

				var saved_grp_id = jQuery('#custom_saved_groups').val();

				jQuery.ajax({
					url : '<?php echo filter_var(admin_url() . 'admin-ajax.php'); ?>',

					type : 'post',
					data : {
						action : 'plgfyga_group_atributes_sorting',				

						saved_grp_id : saved_grp_id

					},
					success : function( response ) {

						var dataa=JSON.parse(response);
						
						jQuery('.attribute_taxonomy').val('');

						if (dataa.length == 0 ) {
							jQuery('#my_custom_load_button').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Load');

							jQuery('#my_custom_load_button').prop('disabled', false);
							jQuery('body').css('cursor' , 'unset');
						}


						for (var i = 0; i < dataa.length; i++) {

							(function(i){
								setTimeout(function(){

									if (jQuery("[data-taxonomy=pa_"+dataa[i]+"]").length == 0) {
										add_attribute_to_list('pa_'+dataa[i]);
									}

									if (dataa.length == (i + 1)) {
										jQuery('#my_custom_load_button').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Load');
										jQuery('#my_custom_load_button').prop('disabled', false);
										jQuery('body').css('cursor' , 'unset');
									}


								}, 1000 * i);
							}(i));
						}
					}
				});
			});



			jQuery('body').on('click', '.save_attributes', function () {

				var create_for_this_product = jQuery('#create_for_this_product').prop('checked');


				jQuery.ajax({
					url : '<?php echo filter_var(admin_url() . 'admin-ajax.php'); ?>',

					type : 'post',
					data : {
						action : 'plgfyga_save_final_array_to_use',			

						final_array : jQuery('#custom_saved_groups').val(),
						create_for_this_product : create_for_this_product,
						pro_id : '<?php echo esc_attr(get_the_ID()); ?>'
					},
					success : function( response ) {
					}
				});
			})



			function get_new_attribute_list_item_html( indexInList, globalAttributeId ) {
				return new Promise( function ( resolve, reject ) {
					jQuery.post( {
						url: woocommerce_admin_meta_boxes.ajax_url,
						data: {
							action: 'woocommerce_add_attribute',
							taxonomy: globalAttributeId ?? '',
							i: indexInList,
							security: woocommerce_admin_meta_boxes.add_attribute_nonce,
						},
						success: function ( newAttributeListItemHtml ) {
							resolve( newAttributeListItemHtml );
						},
						error: function ( jqXHR, textStatus, errorThrown ) {
							reject( { jqXHR, textStatus, errorThrown } );
						}
					} );
				} );
			}

			function block_attributes_tab_container() {
				const $attributesTabContainer = jQuery( '#product_attributes' );

				$attributesTabContainer.block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );
			}

			async function add_attribute_to_list( globalAttributeId ) {
				// console.log(globalAttributeId);
				try {
					block_attributes_tab_container();

					const numberOfAttributesInList = jQuery( '.product_attributes .woocommerce_attribute' ).length;
					const newAttributeListItemHtml = await get_new_attribute_list_item_html( numberOfAttributesInList, globalAttributeId );

					const $attributesListContainer = jQuery( '#product_attributes .product_attributes' );

					const $attributeListItem = jQuery( newAttributeListItemHtml ).appendTo( $attributesListContainer );

					show_and_hide_controls( $attributeListItem );

			init_select_controls(); // make sure any new select controls in the new list item are initialized

			update_attribute_row_indexes();

			toggle_expansion_of_attribute_list_item( $attributeListItem );

			// jQuery.maybe_disable_save_button();
		} catch ( error ) {
			alert( woocommerce_admin_meta_boxes.i18n_add_attribute_error_notice );
			throw error;
		} finally {
			unblock_attributes_tab_container();
		}
	}

	function unblock_attributes_tab_container() {
		const $attributesTabContainer = jQuery( '#product_attributes' );
		$attributesTabContainer.unblock();
	}

	function update_attribute_row_indexes() {
		jQuery( '.product_attributes .woocommerce_attribute' ).each( function (
			index,
			el
			) {
			jQuery( '.attribute_position', el ).val(
				parseInt(
					jQuery( el ).index(
						'.product_attributes .woocommerce_attribute'
						),
					10
					)
				);
		} );
	}

	function show_and_hide_controls( context ) {
		var product_type = jQuery( 'select#product-type' ).val();
		var is_virtual = jQuery( 'input#_virtual:checked' ).length;
		var is_downloadable = jQuery( 'input#_downloadable:checked' ).length;

		// Hide/Show all with rules.
		var hide_classes = '.hide_if_downloadable, .hide_if_virtual';
		var show_classes = '.show_if_downloadable, .show_if_virtual';

		jQuery.each( woocommerce_admin_meta_boxes.product_types, function (
			index,
			value
			) {
			hide_classes = hide_classes + ', .hide_if_' + value;
			show_classes = show_classes + ', .show_if_' + value;
		} );

		jQuery( hide_classes, context ).show();
		jQuery( show_classes, context ).hide();

		// Shows rules.
		if ( is_downloadable ) {
			jQuery( '.show_if_downloadable', context ).show();
		}
		if ( is_virtual ) {
			jQuery( '.show_if_virtual', context ).show();
		}

		jQuery( '.show_if_' + product_type, context ).show();

		// Hide rules.
		if ( is_downloadable ) {
			jQuery( '.hide_if_downloadable', context ).hide();
		}
		if ( is_virtual ) {
			jQuery( '.hide_if_virtual', context ).hide();
		}

		jQuery( '.hide_if_' + product_type, context ).hide();
	}


	function init_select_controls() {
		jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
	}

	function toggle_expansion_of_attribute_list_item( $attributeListItem ) {
		$attributeListItem.find( 'h3' ).trigger( 'click' );
	}

});
</script> 
	<?php
}

add_action( 'woocommerce_product_options_attributes', 'my_custom_product_attribute_field' );


add_action('save_post_product', 'plugify_custom_attributes_save');
function plugify_custom_attributes_save ( $post_id ) {


	if (isset($_REQUEST['custom_saved_groups'] )) {		
		update_post_meta($post_id, 'custom_saved_groups', array_map( 'sanitize_text_field', wp_unslash($_REQUEST['custom_saved_groups'])));
	} else {
		update_post_meta($post_id, 'custom_saved_groups', ''); 
	}

	if ( isset( $_REQUEST['create_for_this_product'] )) {
		update_post_meta($post_id, 'create_for_this_product', filter_var($_REQUEST['create_for_this_product']));

	} else {
		update_post_meta($post_id, 'create_for_this_product', 'off');

	}
}

function plgfyga_custom_taxonomy() {
	$labels = array(
		'name'              => _x( 'Group Attributes Categories', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Group Attribute Category', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Category', 'textdomain' ),
		'all_items'         => __( 'All Category', 'textdomain' ),
		'parent_item'       => __( 'Parent Category', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Category:', 'textdomain' ),
		'edit_item'         => __( 'Edit Category', 'textdomain' ),
		'update_item'       => __( 'Update Category', 'textdomain' ),
		'add_new_item'      => __( 'Add New Category', 'textdomain' ),
		'new_item_name'     => __( 'New Category Name', 'textdomain' ),
		'menu_name'         => __( 'Category', 'textdomain' ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_in_nav_menus' => true,
		'show_ui'           => true,
		'show_tagcloud'     => true,
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'attr_taxonomy' ), 
		'query_var'         => true,
		'capabilities'      => array(),
	);

	register_taxonomy( 'attr_taxonomy', array( 'plgfyga_grp_attr' ), $args ); 
}
add_action( 'init', 'plgfyga_custom_taxonomy' );

function plgfyga_add_taxonomy_to_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		'Group Attributes Categories',
		'Group Attributes Category',
		'manage_options',
		'edit-tags.php?taxonomy=attr_taxonomy&post_type=plgfyga_grp_attr'
	);
}
add_action( 'admin_menu', 'plgfyga_add_taxonomy_to_menu' );


function plgfyga_highlight_product_menu_script() {
	global $post_type, $taxonomy;
	if ( 'plgfyga_grp_attr' == $post_type && 'attr_taxonomy' == $taxonomy) {
		?>
		<script>
			jQuery(document).ready(function($){
				jQuery("#menu-posts-product").find("a:first").addClass("wp-has-current-submenu");
				jQuery("#menu-posts-product").addClass("wp-has-current-submenu");
				jQuery("#menu-posts-product").find("a:first").removeClass("wp-not-current-submenu");
				jQuery("#menu-posts-product").removeClass("wp-not-current-submenu");
				jQuery("#menu-posts-product").find('a[href="edit-tags.php?taxonomy=attr_taxonomy&post_type=plgfyga_grp_attr"]').parent().addClass("current");
			});
		</script>;
		<?php
	}
}
	add_action('admin_footer', 'plgfyga_highlight_product_menu_script');



function plugify_remove_quick_edit_button ( $actions ) {
	global $post;
	if ( get_post_type($post) === 'plgfyga_grp_attr' ) {
		unset($actions['inline hide-if-no-js']);
		unset( $actions['view'] );
	}
	return $actions;
}
add_filter('post_row_actions', 'plugify_remove_quick_edit_button', 10, 1);


//function my_admin_scripts() {
//	wp_enqueue_script( 'my-custom-admin-script', get_stylesheet_directory_uri() . '/js/admin-script.js', array( 'jquery' ), '1.0.0', true );
//}
//add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );



