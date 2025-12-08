<?php
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_MetaBoxes {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	  /**
	   * Control flag for allowing meta box save.
	   *
	   * @var boolean
	   */
	private static $allow_save_meta = true; // Default to true to allow saving unless specified otherwise.


	 /**
	  * Sets the flag to allow or disallow saving meta boxes.
	  *
	  * @param boolean $allow Whether to allow saving.
	  */
	public static function set_allow_save_meta( $allow ) {
		self::$allow_save_meta = $allow;
	}

	/**
	 * Registers a meta box for displaying delivery driver information on WooCommerce Order admin pages.
	 * The function conditionally sets the target screen based on whether the high-performance order screen (HPOS) is enabled.
	 * If HPOS is enabled, it targets the WooCommerce page screen ID for 'shop-order'; otherwise, it defaults to 'shop_order'.
	 * This meta box, titled 'Delivery Driver', is added to the side panel of the order edit screen with default priority.
	 */
	public function add_metaboxes() {

		// Determine the correct screen based on whether the high-performance order screen is enabled.
		// This utilizes a conditional check through the lddfw_is_hpos_enabled() function.
		$screen = lddfw_is_hpos_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box(
			'lddfw_metaboxes',
			__( 'Delivery Driver', 'lddfw' ),
			array( $this, 'create_metaboxes' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Building the metabox.
	 */
	public function create_metaboxes() {
		global $post, $theorder;

		// Determine if we're working with an order object or a post object.
		$order = lddfw_is_hpos_enabled() && ( $theorder instanceof WC_Order ) ? $theorder : wc_get_order( $post->ID );

		echo '<input type="hidden" name="lddfw_metaboxes_key" id="lddfw_metaboxes_key" value="' . esc_attr( wp_create_nonce( 'lddfw-save-order' ) ) . '" />';

		$lddfw_driverid = $order->get_meta( 'lddfw_driverid' );

		echo '<div class="lddfw-driver-box">
	<label>' . esc_html( __( 'Driver', 'lddfw' ) ) . '</label>';
		$drivers = LDDFW_Driver::lddfw_get_drivers();

		echo esc_html( LDDFW_Driver::lddfw_driver_drivers_selectbox( $drivers, $lddfw_driverid, $order->get_id(), '' ) );

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				// Driver Commission.
				echo '<p>
			<label>' . esc_html( __( 'Driver Commission', 'lddfw' ) ) . '</label>
			<br>' . lddfw_currency_symbol() . ' <input size="5" name="lddfw_driver_commission" id="lddfw_driver_commission" type="text" value="' . esc_attr( $order->get_meta( 'lddfw_driver_commission' ) ) . '">';
				$lddfw_driver_commission_note = $order->get_meta( '_lddfw_driver_commission_note' );
				if ( '' !== $lddfw_driver_commission_note ) {
					echo '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $lddfw_driver_commission_note ) . '"  aria-label="' . esc_attr( $lddfw_driver_commission_note ) . '"></span>';
					
				}

				

				echo '</p>';

				// Delivery Note to the Driver.
				$lddfw_delivery_note_to_driver = $order->get_meta( '_lddfw_delivery_note_to_driver' );
				echo '<p>
			<label>' . esc_html( __( 'Note to the driver', 'lddfw' ) ) . '</label>
			<textarea type="text" style="width: 100%;height: 50px;" name="_lddfw_delivery_note_to_driver" id="_lddfw_delivery_note_to_driver" class="input-text" >' . esc_attr( $lddfw_delivery_note_to_driver ) . '</textarea>';
				echo '</p>';

			}
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( has_filter( 'lddfw_delivery_driver_metabox' ) ) {
					echo apply_filters( 'lddfw_delivery_driver_metabox', $order );
				}
			}
		}

		/* driver note */
		$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
		if ( '' !== $lddfw_driver_note ) {
			echo '<p><label>' . esc_html( __( 'Driver Note', 'lddfw' ) ) . '</label><br>';
			echo $lddfw_driver_note;
			echo '</p>';
		}

		$lddfw_delivered_date = $order->get_meta( 'lddfw_delivered_date' );
		if ( '' !== $lddfw_delivered_date ) {
			echo '<p><label>' . esc_html( __( 'Delivered Date', 'lddfw' ) ) . '</label><br>';
			echo $lddfw_delivered_date;
			echo '</p>';
		}

		$lddfw_failed_attempt_date = $order->get_meta( 'lddfw_failed_attempt_date' );
		if ( '' !== $lddfw_failed_attempt_date ) {
			echo '<p><label>' . esc_html( __( 'Failed Attempt Date', 'lddfw' ) ) . '</label><br>';
			echo $lddfw_failed_attempt_date;
			echo '</p>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {

				// Signature.
				$lddfw_order_signature = $order->get_meta( 'lddfw_order_signature', false );
				if ( ! empty( $lddfw_order_signature ) ) {
					echo '<p><label>' . esc_html( __( 'Signature', 'lddfw' ) ) . '</label><br> 
					<div class="lddfw-gallery-container">';

					 // Ensure $lddfw_order_signature is an array
					$lddfw_order_signature = is_array( $lddfw_order_signature ) ? $lddfw_order_signature : array( $lddfw_order_signature );

					 // Sort the meta data by ID in descending order (last added first).
					 usort( $lddfw_order_signature, function( $a, $b ) {
						if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
							return 0;
						}
						return $b->id <=> $a->id; // <-- reversed here
					});

					foreach ( $lddfw_order_signature as $meta_data ) {
						if ( $meta_data instanceof WC_Meta_Data ) {
							$value = $meta_data->value; // Get the value from WC_Meta_Data object

							// Now $value should be a string or whatever format you're expecting
							// Make sure to validate $value's format before using it
							echo '<a href="' . esc_attr( $value ) . '" class="lddfw_order_image" target="_blank"><img  src="' . esc_attr( $value ) . '"></a>';
						}
					}
					echo '</div></p>';
				}

				// Display Delivery Photo(s).
				$lddfw_order_delivery_image = $order->get_meta( 'lddfw_order_delivery_image', false ); // Get raw meta data.
				if ( ! empty( $lddfw_order_delivery_image ) ) {
					echo '<p><label>' . esc_html( __( 'Photos', 'lddfw' ) ) . '</label><br>
						<div class="lddfw-gallery-container">';
					if ( is_array( $lddfw_order_delivery_image ) ) {
						
						 // Sort the meta data by ID in descending order (last added first).
						 usort( $lddfw_order_delivery_image, function( $a, $b ) {
							if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
								return 0;
							}
							return $b->id <=> $a->id; // <-- reversed here
						});
						
						// Process the image data the same way as in class-lddfw-order.php
						foreach ( $lddfw_order_delivery_image as $image ) {
							if ( is_a( $image, 'WC_Meta_Data' ) ) {
								$data = $image->get_data();
								if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
									$image_url = $data['value'];
									echo '<a href="' . esc_url( $image_url ) . '" class="lddfw_order_image" target="_blank">';
									echo '<img src="' . esc_attr( $image_url ) . '">';
									echo '</a>';
								}
							}
						}
					} else {
						// Display single image (original functionality)
						echo '<a href="' . esc_url( $lddfw_order_delivery_image ) . '" class="lddfw_order_image" target="_blank">';
						echo '<img src="' . esc_attr( $lddfw_order_delivery_image ) . '">';
						echo '</a>';
					}
					echo '</div></p>';
				}
			}
		}
		echo '</div> ';
	}




	/**
	 * Save the Metabox Data
	 *
	 * @param int    $post_id post number.
	 * @param object $post post object.
	 */
	public function save_metaboxes( $post_id, $post ) {

		if ( ! self::$allow_save_meta || self::$saved_meta_boxes ) {
			return;
		}

		self::$saved_meta_boxes = true;

		$post_id = absint( $post_id );

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( ! isset( $_POST['lddfw_metaboxes_key'] ) || ! wp_verify_nonce( $_POST['lddfw_metaboxes_key'], 'lddfw-save-order' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$order  = wc_get_order( $post_id );
		$driver = new LDDFW_Driver();

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {

				// Save the driver commissions.
				if ( isset( $_POST['lddfw_driver_commission'] ) ) {
					$lddfw_driver_commission = sanitize_text_field( wp_unslash( $_POST['lddfw_driver_commission'] ) );
					if ( is_numeric( $lddfw_driver_commission ) ) {

						$order->update_meta_data( 'lddfw_driver_commission', $lddfw_driver_commission );
						lddfw_update_sync_order( $post_id, 'lddfw_driver_commission', $lddfw_driver_commission );

					} else {

						$order->delete_meta_data( 'lddfw_driver_commission' );
						lddfw_update_sync_order( $post_id, 'lddfw_driver_commission', '0' );

					}
				}

				// Save delivery note to driver.
				if ( isset( $_POST['_lddfw_delivery_note_to_driver'] ) ) {
					$lddfw_delivery_note_to_driver = sanitize_text_field( wp_unslash( $_POST['_lddfw_delivery_note_to_driver'] ) );
					$order->update_meta_data( '_lddfw_delivery_note_to_driver', $lddfw_delivery_note_to_driver );
				}
			}
		}

		if ( isset( $_POST['lddfw_driverid'] ) ) {
			$lddfw_driverid                            = sanitize_text_field( wp_unslash( $_POST['lddfw_driverid'] ) );
			$lddfw_driver_order_meta['lddfw_driverid'] = $lddfw_driverid;
		}

		foreach ( $lddfw_driver_order_meta as $key => $value ) {
			/**
			 * Cycle through the $thccbd_meta array!
			 */
			if ( 'revision' === $post->post_type ) {
				/**
				 * Don't store custom data twice
				 */
				return;
			}

			$value = implode( ',', (array) $value );

			if ( 'shop_order' === OrderUtil::get_order_type( $post_id ) ) {
				$driver->assign_delivery_driver( $post_id, $value, 'store' );

			}

			if ( ! $value ) {
				/**
				 * Delete if blank
				 */

				$order->delete_meta_data( $key );
				lddfw_update_sync_order( $post_id, $key, '0' );

			}
		}

		$order->save();

		// Remove the flag after saving is done.
		self::$saved_meta_boxes = false;
	}
}


