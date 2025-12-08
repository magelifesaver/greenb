<?php
/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Manger {

	/**
	 * Manager query
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_get_managers() {
		$args = array(
			'role'    => pwddm_manager_role(),
			'orderby' => 'meta_value ASC,display_name ASC',
		);
		return get_users( $args );
	}

	/**
	 * Manager selectbox
	 *
	 * @param int $manager_id user id number.
	 * @return void
	 */
	public function pwddm_manager_selectbox( $manager_id, $name ) {
			echo '<select name="' . $name . '" id="' . $name . '" class="widefat">';
			echo '<option value="">' . esc_html( __( 'Select a manager', 'pwddm' ) ) . '</option>';
			$managers = $this->pwddm_get_managers();
		foreach ( $managers as $manager ) {
			$manager_name = $manager->display_name;
			$selected     = ( intval( $manager_id ) === $manager->ID ) ? 'selected' : '';
			echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $manager->ID ) . '">' . esc_html( $manager_name ) . '</option>';
		}
			echo '</optgroup></select>';
	}





	/**
	 * Edit driver form
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_settings_form( $seller_id ) {
		$pwddm_driver_commission_type  = get_user_meta( $seller_id, 'pwddm_driver_commission_type', true );
		$pwddm_driver_commission_value = get_user_meta( $seller_id, 'pwddm_driver_commission_value', true );
		$html                          = '<div class="container">
			<div class="row">
				<div class="col-12">';

				$html     .= '<form service="pwddm_edit_settings" class="pwddm_form">
						<div class="pwddm_alert_wrap"></div>
						<div class="pwddm_wrap">
						';
					$html .= '<div class="form-group row">
							<label class="col-sm-2 col-form-label" for="pwddm_driver_commission_type">' . esc_html( __( 'Driver Commissions', 'pwddm' ) ) . '</label>
							<div class="col-sm-8">
								<select name="pwddm_driver_commission_type" class="form-control" id="pwddm_driver_commission_type">
									<option value="" >' . esc_html( __( 'Select' ) ) . '</option>
									<option value="fixed" ' . selected( esc_attr( $pwddm_driver_commission_type ), 'fixed', false ) . '">' . esc_html( __( 'Fixed Price' ) ) . '</option>
									<option value="delivery_percentage" ' . selected( esc_attr( $pwddm_driver_commission_type ), 'delivery_percentage', false ) . '">' . esc_html( __( 'Delivery total percentage' ) ) . '</option>
									<option value="order_percentage" ' . selected( esc_attr( $pwddm_driver_commission_type ), 'order_percentage', false ) . '">' . esc_html( __( 'Order total percentage' ) ) . '</option>
								</select>
								<span>' . esc_html( __( 'Set commissions for your drivers only.' ) ) . '</span>
							</div>
							<div class="col-sm-2">
								<div id="pwddm_driver_commission_div" style="display:inline-block;">
									<div id="pwddm_driver_commission_value_wrap" style="display: none;">
										<span id="pwddm_driver_commission_symbol_currency" style="display:none">' . lddfw_currency_symbol() . '</span>
										<span id="pwddm_driver_commission_symbol_percentage" style="display:none">%</span>
										<input class="form-control" type="text" size="5" name="pwddm_driver_commission_value" id="pwddm_driver_commission_value" value="' . $pwddm_driver_commission_value . '">
									</div>
								</div>
							</div>
							</div>
						</div>';

						// buttons
						$html .= '<br><br><br>
								<div class="d-grid gap-2 col-12 col-md-6 mx-auto">
								<button style="margin:0px;" class="pwddm_submit_btn btn btn-lg btn-primary btn-block" type="submit">
								' . esc_html( __( 'Update', 'pwddm' ) ) . '
								</button>
								<button style="display:none;margin:0px;" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html( __( 'Loading', 'pwddm' ) ) . '
								</button>
								</div>
						</form>';

						$html .= '</div>
						</div>
			</div>
		</div>';
		return $html;
	}




	/**
	 * Edit settings service.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_edit_settings_service() {
		$error  = '';
		$result = '0';
		// Security check.
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a manager on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				// Check manager.
				$manager = wp_get_current_user();
				if ( ! in_array( 'Delivery_manager', (array) $manager->roles, true ) ) {
					// No manager.
					$error = __( 'You are not a manager.', 'pwddm' );
				} else {
					$manager_id                    = $manager->ID;
					$pwddm_driver_commission_type  = ( isset( $_POST['pwddm_driver_commission_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_driver_commission_type'] ) ) : '';
					$pwddm_driver_commission_value = ( isset( $_POST['pwddm_driver_commission_value'] ) && '' !== $pwddm_driver_commission_type ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_driver_commission_value'] ) ) : '';

					update_user_meta( $manager_id, 'pwddm_driver_commission_type', $pwddm_driver_commission_type );
					update_user_meta( $manager_id, 'pwddm_driver_commission_value', $pwddm_driver_commission_value );
					$result = '1';
					$error  = __( 'The settings successfully updated.', 'pwddm' );
				}
			}
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}

}
