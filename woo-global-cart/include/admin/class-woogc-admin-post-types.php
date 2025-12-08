<?php
/**
 * Post Types Admin
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

    defined( 'ABSPATH' ) || exit;
    
    /**
     * WooGC_Admin_Post_Types Class.
     *
     * Handles the edit posts views and some functionality on the edit post screen for WC post types.
     */
    class WooGC_Admin_Post_Types extends WC_Admin_Post_Types{
        
	    
	    /**
	     * Output custom columns for coupons.
	     * @param string $column
	     */
	    public function render_shop_order_columns( $column ) {
		    global $post, $woocommerce, $the_order;

		    if ( empty( $the_order ) || $the_order->id != $post->ID ) {
			    $the_order = wc_get_order( $post->ID );
		    }

		    switch ( $column ) {
			    case 'order_status' :

				    printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ) );

			    break;
			    case 'order_date' :

				    if ( '0000-00-00 00:00:00' == $post->post_date ) {
					    $t_time = $h_time = __( 'Unpublished', 'woocommerce' );
				    } else {
					    $t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
					    $h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
				    }

				    echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post ) ) . '</abbr>';

			    break;
			    case 'customer_message' :
				    if ( $the_order->customer_message ) {
					    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $the_order->customer_message ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				    } else {
					    echo '<span class="na">&ndash;</span>';
				    }

			    break;
			    case 'order_items' :

				    echo '<a href="#" class="show_order_items">' . apply_filters( 'woocommerce_admin_order_item_count', sprintf( _n( '%d item', '%d items', $the_order->get_item_count(), 'woocommerce' ), $the_order->get_item_count() ), $the_order ) . '</a>';

				    if ( sizeof( $the_order->get_items() ) > 0 ) {

					    echo '<table class="order_items" cellspacing="0">';

					    foreach ( $the_order->get_items() as $item ) {
						        
                            $product        = apply_filters( 'woocommerce_order_item_product', $the_order->get_product_from_item( $item ), $item );
						    $item_meta      = new WC_Order_Item_Meta( $item, $product );
						    $item_meta_html = $item_meta->display( true, true );
                            
                            if( isset($item['blog_id']) ) 
                                switch_to_blog( $item['blog_id'] );
                                
						    ?>
						    <tr class="<?php echo apply_filters( 'woocommerce_admin_order_item_class', '', $item, $the_order ); ?>">
							    <td class="qty"><?php echo absint( $item['qty'] ); ?></td>
							    <td class="name">
								    <?php  if ( $product ) : ?>
									    <?php echo ( wc_product_sku_enabled() && $product->get_sku() ) ? $product->get_sku() . ' - ' : ''; ?><a href="<?php echo get_edit_post_link( $product->id ); ?>" title="<?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?>"><?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?></a>
								    <?php else : ?>
									    <?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?>
								    <?php endif; ?>
								    <?php if ( ! empty( $item_meta_html ) ) : ?>
									    <?php echo wc_help_tip( $item_meta_html ); ?>
								    <?php endif; ?>
							    </td>
						    </tr>
						    <?php
                            
                            restore_current_blog();
					    }

					    echo '</table>';

				    } else echo '&ndash;';
			    break;
			    case 'billing_address' :

				    if ( $address = $the_order->get_formatted_billing_address() ) {
					    echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
				    } else {
					    echo '&ndash;';
				    }

				    if ( $the_order->billing_phone ) {
					    echo '<small class="meta">' . __( 'Tel:', 'woocommerce' ) . ' ' . esc_html( $the_order->billing_phone ) . '</small>';
				    }

			    break;
			    case 'shipping_address' :

				    if ( $address = $the_order->get_formatted_shipping_address() ) {
					    echo '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) .'</a>';
				    } else {
					    echo '&ndash;';
				    }

				    if ( $the_order->get_shipping_method() ) {
					    echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
				    }

			    break;
			    case 'order_notes' :

				    if ( $post->comment_count ) {

					    // check the status of the post
					    $status = ( 'trash' !== $post->post_status ) ? '' : 'post-trashed';

					    $latest_notes = get_comments( array(
						    'post_id'   => $post->ID,
						    'number'    => 1,
						    'status'    => $status
					    ) );

					    $latest_note = current( $latest_notes );

					    if ( isset( $latest_note->comment_content ) && $post->comment_count == 1 ) {
						    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					    } elseif ( isset( $latest_note->comment_content ) ) {
						    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content . '<br/><small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), $post->comment_count - 1 ) . '</small>' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					    } else {
						    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $post->comment_count, 'woocommerce' ), $post->comment_count ) ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					    }

				    } else {
					    echo '<span class="na">&ndash;</span>';
				    }

			    break;
			    case 'order_total' :
				    echo $the_order->get_formatted_order_total();

				    if ( $the_order->payment_method_title ) {
					    echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->payment_method_title ) . '</small>';
				    }
			    break;
			    case 'order_title' :

				    if ( $the_order->user_id ) {
					    $user_info = get_userdata( $the_order->user_id );
				    }

				    if ( ! empty( $user_info ) ) {

					    $username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

					    if ( $user_info->first_name || $user_info->last_name ) {
						    $username .= esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), ucfirst( $user_info->first_name ), ucfirst( $user_info->last_name ) ) );
					    } else {
						    $username .= esc_html( ucfirst( $user_info->display_name ) );
					    }

					    $username .= '</a>';

				    } else {
					    if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
						    $username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->billing_first_name, $the_order->billing_last_name ) );
					    } else if ( $the_order->billing_company ) {
						    $username = trim( $the_order->billing_company );
					    } else {
						    $username = __( 'Guest', 'woocommerce' );
					    }
				    }

				    printf( _x( '%s by %s', 'Order number by X', 'woocommerce' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>', $username );

				    if ( $the_order->billing_email ) {
					    echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->billing_email ) . '">' . esc_html( $the_order->billing_email ) . '</a></small>';
				    }

				    echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'woocommerce' ) . '</span></button>';

			    break;
			    case 'order_actions' :

				    ?><p>
					    <?php
						    do_action( 'woocommerce_admin_order_actions_start', $the_order );

						    $actions = array();

						    if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) {
							    $actions['processing'] = array(
								    'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
								    'name'      => __( 'Processing', 'woocommerce' ),
								    'action'    => "processing"
							    );
						    }

						    if ( $the_order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
							    $actions['complete'] = array(
								    'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
								    'name'      => __( 'Complete', 'woocommerce' ),
								    'action'    => "complete"
							    );
						    }

						    $actions['view'] = array(
							    'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
							    'name'      => __( 'View', 'woocommerce' ),
							    'action'    => "view"
						    );

						    $actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );

						    foreach ( $actions as $action ) {
							    printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						    }

						    do_action( 'woocommerce_admin_order_actions_end', $the_order );
					    ?>
				    </p><?php

			    break;
		    }
	    }
       
    }
