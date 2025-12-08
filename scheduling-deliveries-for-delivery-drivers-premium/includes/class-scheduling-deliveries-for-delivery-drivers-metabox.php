<?php
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    sdfdd
 * @subpackage sdfdd/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers_
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers_/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Scheduling_Deliveries_For_Delivery_Drivers_MetaBoxes {

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
	public function add_metaboxes( $order ) {

		// Fetch the delivery date and time from the order's metadata.
		$delivery_date = $order->get_meta( '_lddfw_delivery_date' );
		$delivery_time = $order->get_meta( '_lddfw_delivery_time' );

		// Explode the delivery time into 'from_time' and 'to_time' using the ' - ' delimiter.
		$time_parts = explode( ' - ', $delivery_time );

		// Assign the parts to the respective variables.
		$from_time = isset( $time_parts[0] ) ? trim( $time_parts[0] ) : '';
		$to_time   = isset( $time_parts[1] ) ? trim( $time_parts[1] ) : '';

		echo '<p>' . esc_html__( 'Scheduled Delivery Date:', 'scheduling-deliveries-for-delivery-drivers' ) . '
		<br>';
		echo esc_html( sdfdd_get_formatted_order_delivery_date_time( $order ) );
		echo '</p>';

		echo ' <button class="button" id="toggleEditLink" onclick="toggleDeliveryDateFields(event)" data-editing="false">' . esc_html__( 'Update Delivery Date', 'scheduling-deliveries-for-delivery-drivers' ) . '</button>';
		echo '<div id="sdfdd_delivery-date-fields" style="display: none;">';
		// Output the delivery date and time with labels.
		echo '<p>' . esc_html__( 'Delivery Date:', 'scheduling-deliveries-for-delivery-drivers' );
		echo sdfdd_get_delivery_date_input( '_lddfw_delivery_date', $delivery_date, '' );
		echo '</p> 
		<input type="hidden" name="sdfdd_metaboxes_key" id="sdfdd_metaboxes_key" value="' . esc_attr( wp_create_nonce( 'sdfdd-save-order' ) ) . '" />';

		echo '<p>' . esc_html__( 'Delivery Time:', 'scheduling-deliveries-for-delivery-drivers' ) . '<br>';
		echo sdfdd_get_delivery_time_select( '_lddfw_delivery_from_time', $from_time, __( 'From hour', 'scheduling-deliveries-for-delivery-drivers' ) );
		echo '-';
		echo sdfdd_get_delivery_time_select( '_lddfw_delivery_to_time', $to_time, __( 'To hour', 'scheduling-deliveries-for-delivery-drivers' ) );
		echo '</p>';
		echo '</div>';
		?>
		<script>
		function toggleDeliveryDateFields(event) {
			event.preventDefault(); // Prevent the default link behavior.
			var fieldsDiv = document.getElementById('sdfdd_delivery-date-fields');
			var toggleLink = document.getElementById('toggleEditLink');
			var isEditing = toggleLink.getAttribute('data-editing') === 'true'; // Check if currently editing.
	
			if (isEditing) {
				// If editing, hide the fields and change link text back to "Update".
				fieldsDiv.style.display = 'none';
				toggleLink.innerHTML = '<?php echo esc_html__( 'Update Delivery Date', 'scheduling-deliveries-for-delivery-drivers' ); ?>';
				toggleLink.setAttribute('data-editing', 'false');
			} else {
				// If not editing, show the fields and change link text to "Cancel".
				fieldsDiv.style.display = 'block';
				toggleLink.innerHTML = '<?php echo esc_html__( 'Cancel Update', 'scheduling-deliveries-for-delivery-drivers' ); ?>';
				toggleLink.setAttribute('data-editing', 'true');
			}
	
			// Set a flag to indicate that the fields have been opened for editing.
			var editFlag = document.getElementById('sdfdd_delivery_date_edited');
			if (!editFlag) {
				editFlag = document.createElement('input');
				editFlag.type = 'hidden';
				editFlag.name = 'sdfdd_delivery_date_edited';
				editFlag.id = 'sdfdd_delivery_date_edited';
				editFlag.value = '1';
				document.querySelector('#sdfdd_delivery-date-fields').appendChild(editFlag); // Append it to your form.
			} else {
				// If the link is set to "Cancel", remove the hidden input so changes won't be saved.
				if (isEditing) {
					editFlag.remove();
				}
			}
		}
		</script>
		<?php

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
		if ( ! isset( $_POST['sdfdd_metaboxes_key'] ) || ! wp_verify_nonce( $_POST['sdfdd_metaboxes_key'], 'sdfdd-save-order' ) ) {
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

		$order = wc_get_order( $post_id );

			/**
			 * Cycle through the $thccbd_meta array!
			 */
		if ( 'revision' === $post->post_type ) {
			/**
			 * Don't store custom data twice
			 */
			return;
		}

		if ( 'shop_order' === OrderUtil::get_order_type( $post_id ) ) {

			if ( isset( $_POST['sdfdd_delivery_date_edited'] ) ) {

				if ( isset( $_POST['_lddfw_delivery_from_time'] ) && isset( $_POST['_lddfw_delivery_to_time'] ) ) {
					$delivery_from_time = sanitize_text_field( wp_unslash( $_POST['_lddfw_delivery_from_time'] ) );
					$delivery_to_time   = sanitize_text_field( wp_unslash( $_POST['_lddfw_delivery_to_time'] ) );

					// Combine both times into a single string (e.g., "10:00 - 12:00").
					$delivery_time = $delivery_from_time . ' - ' . $delivery_to_time;

					// Save the combined delivery time to the order meta.
					$order->update_meta_data( '_lddfw_delivery_time', $delivery_time );
				}

				if ( isset( $_POST['_lddfw_delivery_date'] ) ) {
					$delivery_date = sanitize_text_field( wp_unslash( $_POST['_lddfw_delivery_date'] ) );
					$order->update_meta_data( '_lddfw_delivery_date', $delivery_date );
				}
			}

			$order->save();
		}

		// Remove the flag after saving is done.
		self::$saved_meta_boxes = false;
	}
}


