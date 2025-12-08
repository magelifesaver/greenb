<?php
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */


/**
 * Drivers selectbox
 *
 * @param object $drivers drivers object.
 * @param int    $driver_id user id number.
 * @param int    $order_id order number.
 * @param string $type type.
 * @return void
 */
function pwddm_manager_drivers_selectbox( $drivers, $driver_id, $order_id, $type ) {
	if ( 'bulk' === $type ) {
		echo "<select name='pwddm_managerid_" . esc_attr( $order_id ) . "' id='pwddm_managerid_" . esc_attr( $order_id ) . "'>";
	} else {
		echo "<select name='pwddm_managerid' id='pwddm_managerid_" . esc_attr( $order_id ) . "' order='" . esc_attr( $order_id ) . "' class='widefat'>";
	}
	echo "<option value=''>" . esc_html( __( 'Assign a driver', 'pwddm' ) ) . '</option>
    ';
	$last_availability = '';
	foreach ( $drivers as $driver ) {
		$driver_name    = $driver->display_name;
		$availability   = get_user_meta( $driver->ID, 'pwddm_manager_availability', true );
		$driver_account = get_user_meta( $driver->ID, 'pwddm_manager_account', true );
		$availability   = '1' === $availability ? 'Available' : 'Unavailable';
		$selected       = '';
		if ( intval( $driver_id ) === $driver->ID ) {
			$selected = 'selected';
		}
		if ( $last_availability !== $availability ) {
			if ( '' !== $last_availability ) {
				echo '</optgroup>';
			}
			echo '<optgroup label="' . esc_attr( $availability . ' ' . __( 'drivers', 'pwddm' ) ) . '">';
			$last_availability = $availability;
		}
		if ( '1' === $driver_account || ( '1' != $driver_account && intval( $driver_id ) === $driver->ID ) ) {
			echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
		}
	}
	echo '</optgroup></select>';
}



/**
 * Save the Metabox Data
 *
 * @param int    $post_id post number.
 * @param object $post post object.
 */
function pwddm_manager_save_order_details( $post_id, $post ) {
}

