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
class PWDDM_Driver {
	/**
	 * Drivers query
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public static function pwddm_get_drivers( $manager_id, $type = '' ) {
		global $pwddm_page;
		$number_of_users = -1;
		$driver_array    = array(
			'relation' => 'OR',
			array(
				'sort_available_not_exist' => array(
					'key'     => 'lddfw_driver_availability',
					'compare' => 'NOT EXISTS',
				),
			),
			array(
				'sort_available_exist' => array(
					'key'     => 'lddfw_driver_availability',
					'compare' => 'EXISTS',
				),
			),
		);

		$args = array(
			'role'       => 'driver',
			'meta_query' => array(
				$driver_array,
			),
			'orderby'    => 'sort_available_not_exist ASC,display_name ASC',
			'number'     => $number_of_users,
			'paged'      => $pwddm_page,
		);

		$result = new WP_User_Query( $args );
		return $result;
	}

	/**
	 * All Drivers
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_drivers( $manager_id ) {
		global  $pwddm_manager_drivers;
		$user_query = $this->pwddm_get_drivers( $manager_id );
		$drivers    = $user_query->get_results();

		$html = '
	<div class="container">
	<div class="row">
	<div class="col-12">';
		if ( ! empty( $drivers ) ) {
			$html   .= ' <div class="table-responsive"><table class="table table-striped table-hover">
		<thead class="table-dark">
			<tr>
				<th scope="col">#</th>
				<th scope="col">' . esc_html( __( 'Name', 'pwddm' ) ) . '</th>
				<th scope="col">' . esc_html( __( 'Phone', 'pwddm' ) ) . '</th>
				<th scope="col">' . esc_html( __( 'Email', 'pwddm' ) ) . '</th>
				<th scope="col" class="text-center">' . esc_html( __( 'Account', 'pwddm' ) ) . '</th>
				<th scope="col" class="text-center">' . esc_html( __( 'Claim', 'pwddm' ) ) . '</th>
				<th scope="col" class="text-center">' . esc_html( __( 'Availability', 'pwddm' ) ) . '</th>
				<th scope="col" class="text-center">' . esc_html( __( 'Manager', 'pwddm' ) ) . '</th>
			</tr>
		</thead>
		<tbody>';
			$counter = 1;

			foreach ( $drivers as $user ) {
				$driver_manager_id = get_user_meta( $user->ID, 'pwddm_manager', true );
				if (
					( ( '0' === $pwddm_manager_drivers || '' === strval( $pwddm_manager_drivers ) ) && ( '' === strval( $driver_manager_id ) ) )
					||
					( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
					||
					( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
				) {

					$driver_account      = get_user_meta( $user->ID, 'lddfw_driver_account', true );
					$driver_availability = get_user_meta( $user->ID, 'lddfw_driver_availability', true );
					$driver_claim        = get_user_meta( $user->ID, 'lddfw_driver_claim', true );
					$manager_name        = get_user_meta( $driver_manager_id, 'billing_first_name', true ) . ' ' . get_user_meta( $driver_manager_id, 'billing_last_name', true );
					// Status icons.
					if ( '1' === $driver_availability ) {
						$driver_availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					} else {
						$driver_availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					}

					if ( '1' === $driver_account ) {
						$driver_account_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					} else {
						$driver_account_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					}

					if ( '1' === $driver_claim ) {
						$driver_claim_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					} else {
						$driver_claim_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
					}

					$icon_class = ( strval( $driver_manager_id ) === strval( $manager_id ) ) ? 'pwddm_user_icon' : 'pwddm_user_icon_disable';

					$html .= '
			<tr>
				<th scope="row">' . $counter . '</th>
				<td><a href="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=driver&pwddm_driverid=' . $user->ID ) ) . '" >' . esc_html( $user->first_name . ' ' . $user->last_name ) . '</a></td>
				<td>' . esc_html( $user->billing_phone ) . '</td>
				<td>' . esc_html( $user->user_email ) . '</td>
				<td class="text-center">' . pwddm_premium_feature( '<a href="#" class="' . $icon_class . '" service="pwddm_driver_account_status" driver_id="' . esc_attr( $user->ID ) . '">' . $driver_account_icon . '</a>' ) . '</td>
				<td class="text-center">' . pwddm_premium_feature( '<a href="#" class="' . $icon_class . '" service="pwddm_claim_permission" driver_id="' . esc_attr( $user->ID ) . '">' . $driver_claim_icon . '</a>' ) . '</td>
				<td class="text-center">' . pwddm_premium_feature( '<a href="#" class="' . $icon_class . '" service="pwddm_availability" driver_id="' . esc_attr( $user->ID ) . '">' . $driver_availability_icon . '</a>' ) . '</td>
				<td class="text-center">' . $manager_name . '</td>
			</tr>';
					$counter ++;
				}
			}

			$html .= '</tbody>
		</table></div>';
		} else {
			$html .= '<div class="alert alert-info">' . esc_html( __( 'Drivers not found.', 'pwddm' ) ) . '</div>';
		}
		if ( ! empty( $pagination ) ) {
			$html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
			foreach ( $pagination as $page ) {
				$html .= '<li class="page-item ';
				if ( strpos( $page, 'current' ) !== false ) {
					$html .= ' active';
				}
				$html .= '"> ' . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
			}
			$html .= '</nav></div>';
		}
		$html .= '</div>
		</div>
		</div>';
		return $html;
	}

	/**
	 * Edit driver form
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_edit_driver_form( $user_meta ) {

		$json = json_decode( $this->pwddm_edit_driver_service() );

		$first_name      = $user_meta->first_name;
		$last_name       = $user_meta->last_name;
		$email           = $user_meta->user_email;
		$billing_country = $user_meta->billing_country;
		$phone           = $user_meta->billing_phone;
		$city            = $user_meta->billing_city;
		$company         = $user_meta->billing_company;
		$address_1       = $user_meta->billing_address_1;
		$address_2       = $user_meta->billing_address_2;
		$postcode        = $user_meta->billing_postcode;
		$billing_state   = $user_meta->billing_state;

		$html = '<div class="container">
			<div class="row">
				<div class="col-12">';

				// Check if manager driver.
				$driver_id      = $user_meta->ID;
				$manager        = wp_get_current_user();
				$manager_id     = $manager->ID;
				$driver_manager = get_user_meta( $driver_id, 'pwddm_manager', true );

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {
				// get driver coordinates.
				$route                   = new PWDDM_Route();
				$pwddm_address_latitude  = '';
				$pwddm_address_longitude = '';
				$coordinates             = $route->get_driver_geocode( $driver_id );
				if ( false !== $coordinates && is_array( $coordinates ) ) {
					$pwddm_address_latitude  = $coordinates[0];
					$pwddm_address_longitude = $coordinates[1];
				}
			}
		}

		if ( strval( $driver_manager ) === strval( $manager_id ) ) {

			$html .= '<form service="pwddm_edit_driver" id="pwddm_edit_driver" method="post" action="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=driver&pwddm_driverid=' . $driver_id ) ) . '" id="pwddm_driver_form">
						<input type="hidden" name="pwddm_driverid" value="' . $driver_id . '">
						<input type="hidden" name="pwddm_wpnonce" value="' . wp_create_nonce( 'pwddm-nonce' ) . '">
						<div class="pwddm_alert_wrap">';
			if ( '' !== $json->{'error'} ) {
				if ( $json->{'result'} === '1' ) {
							$html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $json->{'error'} . '
								<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>';
				} else {
						$html .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . $json->{'error'} . '
								<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>';
				}
			}
						$html .= '</div>
						<div class="pwddm_wrap">
						';

				$html .= '<div class="form-group row upload_image_form">
						<label class="col-sm-2 col-form-label" for="upload_image_wrap">' . esc_html( __( 'Photo', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
						<div class="upload_image_wrap"><span class="pwddm_helper"></span>';
				/* driver photo */
				$image    = '';
				$image_id = get_user_meta( $driver_id, 'lddfw_driver_image', true );
			if ( intval( $image_id ) > 0 ) {
				$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
				if ( '' !== $image ) {
					$html .= '<img src="' . $image . '">';
				}
			}
			if ( '' === $image ) {
					$html .= '<img src="' . plugins_url() . '/' . PWDDM_FOLDER . '/public/images/user.png?ver=' . PWDDM_VERSION . '">';
			}
						$html .= '

						</div>
						<div class="custom-file photo_upload" >
							<input type="hidden" class="pwddm_image_input" name="pwddm_image_input" value="" >
							<input type="file" class="custom-file-input pwddm_upload_image"  >
							<label class="custom-file-label" for="upload_image">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="camera" class="svg-inline--fa fa-camera fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 144v288c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V144c0-26.5 21.5-48 48-48h88l12.3-32.9c7-18.7 24.9-31.1 44.9-31.1h125.5c20 0 37.9 12.4 44.9 31.1L376 96h88c26.5 0 48 21.5 48 48zM376 288c0-66.2-53.8-120-120-120s-120 53.8-120 120 53.8 120 120 120 120-53.8 120-120zm-32 0c0 48.5-39.5 88-88 88s-88-39.5-88-88 39.5-88 88-88 88 39.5 88 88z"></path></svg>
							</label>
						</div>
						</div></div>';

						$html .= '<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="pwddm_first_name">' . esc_html( __( 'First name', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="pwddm_first_name" value="' . $first_name . '" class="form-control" id="pwddm_first_name" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'First name', 'pwddm' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="pwddm_last_name">' . esc_html( __( 'Last name', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="pwddm_last_name" value="' . $last_name . '" class="form-control" id="pwddm_last_name" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Last name', 'pwddm' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="pwddm_company">' . esc_html( __( 'Company', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="pwddm_company" value="' . $company . '" class="form-control" id="pwddm_company" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Company', 'pwddm' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="pwddm_address_1">' . esc_html( __( 'Address line 1', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="pwddm_address_1" value="' . $address_1 . '" class="form-control" id="pwddm_address_1" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Address line 1', 'pwddm' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="pwddm_address_2">' . esc_html( __( 'Address line 2', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="pwddm_address_2" value="' . $address_2 . '" class="form-control" id="pwddm_address_2" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Address line 2', 'pwddm' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="pwddm_city">' . esc_html( __( 'City', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="pwddm_city" value="' . $city . '" class="form-control" id="pwddm_city" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'City', 'pwddm' ) ) . '">
						</div>
					</div>
					<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="pwddm_postcode">' . esc_html( __( 'Postcode / ZIP', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="pwddm_postcode" value="' . $postcode . '" class="form-control" id="pwddm_postcode" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Postcode / ZIP', 'pwddm' ) ) . '">
						</div>
					</div>

						';

						global $woocommerce;
						$countries_obj = new WC_Countries();
						$countries     = $countries_obj->__get( 'countries' );

						$default_country       = $countries_obj->get_base_country();
						$default_county_states = $countries_obj->get_states( 'US' );

						$html .= '<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="exampleInputPassword1">' . esc_html( __( 'Country / Region', 'pwddm' ) ) . '</label>';
						$html .= '<div class="col-sm-10"><select id="billing_country" name="pwddm_country" class="form-control">
							<option value="">' . esc_html( __( 'Select Country / Region', 'pwddm' ) ) . '</option>';
			foreach ( $countries as $key => $country ) {
				 $html .= '<option value="' . $key . '" ' . selected( $billing_country, $key, false ) . ' >' . $country . '</option>';
			}
						$html .= '</select>';
						$html .= '</div></div>';

						$html .= '<div class="form-group row">
								  <label class="col-sm-2 col-form-label" for="exampleInputPassword1">' . esc_html( __( 'State / County', 'pwddm' ) ) . '</label>';
						$html .= '<div class="col-sm-10"><select style="display:none" id="billing_state_select" name="billing_state_select" class="form-control">
									<option value="">' . esc_html( __( 'Select State / County', 'pwddm' ) ) . '</option>';
			foreach ( $default_county_states as $key => $state ) {
				$html .= '<option value="' . $key . '" ' . selected( $billing_state, $key, false ) . ' >' . $state . '</option>';
			}
						$html .= '</select>
								  <input type="text" style="display:none" class="form-control" id="billing_state_input"  placeholder="' . esc_html( __( 'State / County', 'pwddm' ) ) . '" value="' . esc_attr( $billing_state ) . '" name="billing_state">';
						$html .= '</div></div>';

						$html .= '<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="pwddm_phone">' . esc_html( __( 'Phone number', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="pwddm_phone" value="' . $phone . '" class="form-control" id="pwddm_phone" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Phone number', 'pwddm' ) ) . '">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label"  for="pwddm_email">' . esc_html( __( 'Email address', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
						<input type="email" name="pwddm_email"  value="' . $email . '"  class="form-control" id="pwddm_email" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Enter email', 'pwddm' ) ) . '">
						</div>
					</div>
					
					<div class="form-group row">
							<label class="col-sm-2 col-form-label"  for="new_password_button">' . esc_html( __( 'Password', 'pwddm' ) ) . '</label>
							<div class="col-sm-10">
								<button type="button" id="new_password_button" class="btn btn-secondary">' . esc_html( __( 'Set New Password', 'pwddm' ) ) . '</button>
								<div class = "row" id = "pwddm_password_holder" style = "display:none" >
									<div class="col-6">
										<input type="text" name="pwddm_password" id="pwddm_password"  value="" class="form-control" id="pwddm_password" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Enter password', 'pwddm' ) ) . '">
									</div>
									<div class="col-6">
										<button  type="button" id="cancel_password_button" class="btn btn-secondary">' . esc_html( __( 'Cancel', 'pwddm' ) ) . '</button>
									</div>
								</div>
							</div>
						</div>
					';

						$html    .= '<div class="form-group row"><label class="col-sm-2 col-form-label" for="lddfw_driver_account">' . esc_html( __( 'Driver account status', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
							<select name="lddfw_driver_account"  class="form-control small-select"  id="lddfw_driver_account">
								<option value="0">' . esc_html( __( 'Not active', 'pwddm' ) ) . '</option>';
					$selected     = get_user_meta( $driver_id, 'lddfw_driver_account', true ) === '1' ? 'selected' : '';
					$html        .= '<option ' . esc_attr( $selected ) . ' value="1">' . esc_html( __( 'Active', 'pwddm' ) ) . '</option>
							</select>
							<small id="emailHelp" class="form-text text-muted">' . esc_html( __( 'Only drivers with active accounts can access the drivers\' panel.', 'pwddm' ) ) . '</small>
						</div>
						</div>

						<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="lddfw_driver_availability"> ' . esc_html( __( 'Driver availability', 'pwddm' ) ) . '</label>
						<div class="col-sm-10">
							<select class="form-control small-select" name="lddfw_driver_availability" id="lddfw_driver_availability">
								<option value="0"> ' . esc_html( __( 'Unavailable', 'pwddm' ) ) . '</option>';
					$selected     = get_user_meta( $driver_id, 'lddfw_driver_availability', true ) === '1' ? 'selected' : '';
					$html        .= '<option  ' . esc_attr( $selected ) . ' value="1"> ' . esc_html( __( 'Available', 'pwddm' ) ) . ' </option>
							</select>
							<small id="emailHelp" class="form-text text-muted">' . esc_html( __( 'The delivery driver availability for work today.', 'pwddm' ) ) . ' </small>
						</div>
						</div>

						<div class="form-group row">
							<label for="lddfw_driver_claim" class="col-sm-2 col-form-label" > ' . esc_html( __( 'Driver can claim orders', 'pwddm' ) ) . ' </label> ';
						$selected = get_user_meta( $driver_id, 'lddfw_driver_claim', true ) === '1' ? 'selected' : '';
						$html    .= '
							<div class="col-sm-10">
								<select name="lddfw_driver_claim"  class="form-control small-select"  id="lddfw_driver_claim">
								<option value="0">' . esc_html( __( 'No', 'pwddm' ) ) . '</option>
								<option ' . esc_attr( $selected ) . ' value = "1" >' . esc_html( __( 'Yes', 'pwddm' ) ) . '</option>
								</select>
								<small id="emailHelp" class="form-text text-muted">' . esc_html( __( 'Give the driver permission to claim orders.', 'pwddm' ) ) . '</small>
							</div>
						</div>';

			if ( pwddm_fs()->is__premium_only() ) {
				if ( pwddm_fs()->can_use_premium_code() ) {

					$html .= '<h4>' . esc_html( __( 'Address Coordinates', 'pwddm' ) ) . '</h4>
								<p>' . esc_html( __( 'Leave blank for auto-filling.', 'pwddm' ) ) . '</p>
								<div class="form-group row">
									<label class="col-sm-2 col-form-label" for="pwddm_address_latitude">' . esc_html( __( 'Latitude', 'pwddm' ) ) . '</label>
									<div class="col-sm-10">
									<input type="text" name="pwddm_address_latitude" value="' . $pwddm_address_latitude . '" class="form-control small-select" id="pwddm_address_latitude" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'latitude', 'pwddm' ) ) . '">
									<small class="form-text text-muted">' . esc_html( __( 'e.g. 37.819722', 'pwddm' ) ) . '</small>
									</div>
								</div>';

						$html .= '<div class="form-group row">
									<label class="col-sm-2 col-form-label" for="pwddm_address_longitude">' . esc_html( __( 'Longitude', 'pwddm' ) ) . '</label>
									<div class="col-sm-10">
									<input type="text" name="pwddm_address_longitude" value="' . $pwddm_address_longitude . '" class="form-control small-select" id="pwddm_address_longitude" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'longitude', 'pwddm' ) ) . '">
									<small class="form-text text-muted">' . esc_html( __( 'e.g. -122.478611', 'pwddm' ) ) . '</small>
									</div>
								</div>';
				}
			}

						// buttons.
						$html .= '
								<div class="d-grid gap-2 col-12 col-md-6 mx-auto">	
								<button class="pwddm_submit_btn btn btn-lg btn-primary btn-block" type="submit">
								' . esc_html( __( 'Update', 'pwddm' ) ) . '
								</button>
								<button style="display:none" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html( __( 'Loading', 'pwddm' ) ) . '
								</button>
								</div>
						</form>';
		} else {
			$html .= '<div class="alert alert-danger" role="alert">'
			. esc_html( __( 'The driver does not belong to you.', 'pwddm' ) ) . '
							</div>';
		}
						$html .= '</div>
						</div>
			</div>
		</div>';
		return $html;
	}

	/**
	 * New driver form
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_new_driver_form() {
		$html = '<div class="container">
			<div class="row">
				<div class="col-12">
					<form service="pwddm_new_driver" class="pwddm_form">
						<div class="pwddm_alert_wrap"></div>
						<div class="pwddm_wrap pwddm_hide_on_success">
						';

					$html .= ' <div class="mb-3">
							<label for="pwddm_first_name" class="form-label">' . esc_html( __( 'First name', 'pwddm' ) ) . '</label>
							<input type="text" name="pwddm_first_name"   class="form-control" id="pwddm_first_name" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'First name', 'pwddm' ) ) . '">
						</div>
						<div class="mb-3">
							<label for="pwddm_last_name" class="form-label">' . esc_html( __( 'Last name', 'pwddm' ) ) . '</label>
							<input type="text" name="pwddm_last_name"    class="form-control" id="pwddm_last_name" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Last name', 'pwddm' ) ) . '">
						</div>
						<div class="mb-3">
							<label for="pwddm_phone" class="form-label">' . esc_html( __( 'Phone number', 'pwddm' ) ) . '</label>
							<input type="text" name="pwddm_phone"   class="form-control" id="pwddm_phone" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Phone number', 'pwddm' ) ) . '">
						</div>
						<div class="mb-3">
							<label for="pwddm_email" class="form-label">' . esc_html( __( 'Email address', 'pwddm' ) ) . '</label>
							<input type="email" name="pwddm_email" class="form-control" id="pwddm_email" aria-describedby="emailHelp" placeholder="' . esc_html( __( 'Enter email', 'pwddm' ) ) . '">
						</div>
						<div class="mb-3">
							<label for="pwddm_password" class="form-label">' . esc_html( __( 'Password', 'pwddm' ) ) . '</label>
							<input type="password" name="pwddm_password"   class="form-control" id="exampleInputPassword1" placeholder="' . esc_html( __( 'Password', 'pwddm' ) ) . '">
						</div>';

						global $woocommerce;
						$countries_obj = new WC_Countries();
						$countries     = $countries_obj->__get( 'countries' );
						$html         .= ' <div class="mb-3">
						<label for="pwddm_country" class="form-label">' . esc_html( __( 'Country', 'pwddm' ) ) . '</label>';
						$html         .= '<select name="pwddm_country" class="form-control">
							<option value="">' . esc_html( __( 'Select country', 'pwddm' ) ) . '</option>';
		foreach ( $countries as $key => $country ) {
			$html .= '<option value="' . $key . '">' . $country . '</option>';
		}
						$html .= '</select>';
						$html .= '</div>';

						// buttons.
						$html .= '
								<div class="d-grid gap-2 col-12 col-md-6 mx-auto">		
								<button class="pwddm_submit_btn btn btn-lg btn-primary btn-block" type="submit">
								' . esc_html( __( 'Submit', 'pwddm' ) ) . '
								</button>
								<button style="display:none" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html( __( 'Loading', 'pwddm' ) ) . '
								</button>
								</div>
						</form>
						</div>
						</div>
			</div>
		</div>';
		return $html;
	}



	/**
	 * Create New driver
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_edit_driver_service() {
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
				if ( ! in_array( pwddm_manager_role(), (array) $manager->roles, true ) ) {
					// No manager.
					$error = __( 'You are not a manager.', 'pwddm' );
				} else {
					$manager_id = $manager->ID;

					$driver_id           = ( isset( $_POST['pwddm_driverid'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_driverid'] ) ) : '';
					$email               = ( isset( $_POST['pwddm_email'] ) ) ? sanitize_email( wp_unslash( $_POST['pwddm_email'] ) ) : '';
					$first_name          = ( isset( $_POST['pwddm_first_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_first_name'] ) ) : '';
					$last_name           = ( isset( $_POST['pwddm_last_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_last_name'] ) ) : '';
					$phone               = ( isset( $_POST['pwddm_phone'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_phone'] ) ) : '';
					$country             = ( isset( $_POST['pwddm_country'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_country'] ) ) : '';
					$company             = ( isset( $_POST['pwddm_company'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_company'] ) ) : '';
					$address_1           = ( isset( $_POST['pwddm_address_1'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_address_1'] ) ) : '';
					$address_2           = ( isset( $_POST['pwddm_address_2'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_address_2'] ) ) : '';
					$city                = ( isset( $_POST['pwddm_city'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_city'] ) ) : '';
					$postcode            = ( isset( $_POST['pwddm_postcode'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_postcode'] ) ) : '';
					$password            = ( isset( $_POST['pwddm_password'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_password'] ) ) : '';
					$driver_account      = ( isset( $_POST['lddfw_driver_account'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_account'] ) ) : '';
					$driver_availability = ( isset( $_POST['lddfw_driver_availability'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_availability'] ) ) : '';
					$driver_claim        = ( isset( $_POST['lddfw_driver_claim'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_claim'] ) ) : '';
					$state               = ( isset( $_POST['billing_state'] ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_state'] ) ) : '';
					$image               = ( isset( $_POST['pwddm_image_input'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_image_input'] ) ) : '';

					if ( '' === $driver_id ) {
						// No driver.
						$error = __( 'Driver number is empty.', 'pwddm' );
					} else {
						// Check for empty fields.
						if ( '' === $email ) {
							// No email.
							$error = __( 'The email field is empty.', 'pwddm' );
						} else {
							if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
								// Invalid Email.
								$error = __( 'The email is invalid.', 'pwddm' );
							} else {
								// Email exist for another user.
								$user    = get_user_by( 'email', $email );
								$user_id = $user->data->ID;
								if ( $user && (string) $user_id !== (string) $driver_id ) {
									$error = __( 'Email exist for another user.', 'pwddm' );
								} else {

									if ( ! user_can( $driver_id, 'driver' ) ) {
										$error = __( 'The user is not a driver.', 'pwddm' );
									} else {

										if ( '' === $first_name ) {
											$error = __( 'First name is empty.', 'pwddm' );
										} else {
											if ( '' === $last_name ) {
												$error = __( 'Last name is empty.', 'pwddm' );
											} else {
												if ( '' === $phone ) {
													$error = __( 'Phone is empty.', 'pwddm' );
												} else {
													if ( '' === $address_1 ) {
														$error = __( 'Address 1 is empty.', 'pwddm' );
													} else {
														if ( '' === $city ) {
															$error = __( 'City is empty.', 'pwddm' );
														} else {
															if ( '' === $country ) {
																$error = __( 'Country is empty.', 'pwddm' );
															} else {
																// Check manager driver.
																$driver_manager = get_user_meta( $driver_id, 'pwddm_manager', true );
																if ( strval( $driver_manager ) !== strval( $manager_id ) ) {
																	$error = __( 'The driver does not belong to you.', 'pwddm' );
																} else {
																	wp_update_user(
																		array(
																			'ID' => $driver_id,
																			'first_name' => $first_name,
																			'last_name' => $last_name,
																			'user_email' => $email,
																			'nickname' => $first_name . ' ' . $last_name,
																		)
																	);
																	update_user_meta( $driver_id, 'billing_first_name', $first_name );
																	update_user_meta( $driver_id, 'billing_last_name', $last_name );
																	update_user_meta( $driver_id, 'billing_company', $company );
																	update_user_meta( $driver_id, 'billing_address_1', $address_1 );
																	update_user_meta( $driver_id, 'billing_address_2', $address_2 );
																	update_user_meta( $driver_id, 'billing_postcode', $postcode );
																	update_user_meta( $driver_id, 'billing_city', $city );
																	update_user_meta( $driver_id, 'billing_state', $state );
																	update_user_meta( $driver_id, 'billing_phone', $phone );
																	update_user_meta( $driver_id, 'billing_country', $country );
																	update_user_meta( $driver_id, 'lddfw_driver_account', $driver_account );
																	update_user_meta( $driver_id, 'lddfw_driver_availability', $driver_availability );
																	update_user_meta( $driver_id, 'lddfw_driver_claim', $driver_claim );

																	if ( pwddm_fs()->is__premium_only() ) {
																		if ( pwddm_fs()->can_use_premium_code() ) {

																			// set driver coordinates.
																			$latitude  = ( isset( $_POST['pwddm_address_latitude'] ) && is_numeric( $_POST['pwddm_address_latitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_address_latitude'] ) ) : '';
																			$longitude = ( isset( $_POST['pwddm_address_longitude'] ) && is_numeric( $_POST['pwddm_address_longitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_address_longitude'] ) ) : '';

																			if ( '' === $latitude || '' === $longitude ) {
																				update_user_meta( $driver_id, 'lddfw_address_latitude', '' );
																				update_user_meta( $driver_id, 'lddfw_address_longitude', '' );
																				$route = new PWDDM_Route();
																				$route->set_driver_geocode( $driver_id );
																			} else {
																				update_user_meta( $driver_id, 'lddfw_address_latitude', $latitude );
																				update_user_meta( $driver_id, 'lddfw_address_longitude', $longitude );
																			}
																		}
																	}

																	if ( '' !== $password ) {
																		wp_set_password( $password, $driver_id );
																	}
																	if ( '' !== $image ) {
																		$image_id = pwddm_add_image_to_media( $image, 'v_driver' );
																		update_user_meta( $driver_id, 'lddfw_driver_image', $image_id );
																	}

																	$result = 1;
																	$error  = __( 'The driver successfully updated.', 'pwddm' );
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}




	/**
	 * Create New driver
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_create_new_driver_service() {
		$error  = '';
		$result = '0';
		// Security check.
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a manager on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				$manager = wp_get_current_user();
				if ( ! in_array( pwddm_manager_role(), (array) $manager->roles, true ) ) {
					// No manager.
					$error = __( 'You are not a manager.', 'pwddm' );
				} else {
					$manager_id = $manager->ID;

					if ( isset( $_POST['pwddm_email'] ) ) {
						$email = sanitize_email( wp_unslash( $_POST['pwddm_email'] ) );
					}
					if ( isset( $_POST['pwddm_password'] ) ) {
						$password = sanitize_text_field( wp_unslash( $_POST['pwddm_password'] ) );
					}
					if ( isset( $_POST['pwddm_last_name'] ) ) {
						$last_name = sanitize_text_field( wp_unslash( $_POST['pwddm_last_name'] ) );
					}
					if ( isset( $_POST['pwddm_first_name'] ) ) {
						$first_name = sanitize_text_field( wp_unslash( $_POST['pwddm_first_name'] ) );
					}
					if ( isset( $_POST['pwddm_phone'] ) ) {
						$phone = sanitize_text_field( wp_unslash( $_POST['pwddm_phone'] ) );
					}
					if ( isset( $_POST['pwddm_country'] ) ) {
						$country = sanitize_text_field( wp_unslash( $_POST['pwddm_country'] ) );
					}

					// Check for empty fields.
					if ( empty( $email ) ) {
						// No email.
						$error = __( 'The email field is empty.', 'pwddm' );
					} else {
						if ( empty( $password ) ) {
							// No password.
							$error = __( 'The password field is empty.', 'pwddm' );
						} else {
							if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
								// Invalid Email.
								$error = __( 'The email is invalid.', 'pwddm' );
							} else {
								// Check if user exists in WordPress database.
								$user = get_user_by( 'email', $email );
								// Bad email.
								if ( $user ) {
									$error = __( 'User is already exist in the system.', 'pwddm' );
								} else {
									if ( empty( $first_name ) ) {
										$error = __( 'First name is empty.', 'pwddm' );
									} else {
										if ( empty( $last_name ) ) {
											$error = __( 'Last name is empty.', 'pwddm' );
										} else {
											if ( empty( $phone ) ) {
												$error = __( 'Phone is empty.', 'pwddm' );
											} else {
												if ( empty( $country ) ) {
													$error = __( 'Country is empty.', 'pwddm' );
												} else {

													$username = $email;
													$user_id  = username_exists( $username );
													if ( ! $user_id && email_exists( $email ) === false ) {
														$user_id = wp_create_user( $username, $password, $email );
														if ( ! is_wp_error( $user_id ) ) {
															$user = get_user_by( 'id', $user_id );
															$user->set_role( 'driver' );
															wp_update_user(
																array(
																	'ID' => $user_id,
																	'first_name' => $first_name,
																	'last_name' => $last_name,
																	'user_email' => $email,
																	'nickname' => $first_name . ' ' . $last_name,
																	'display_name' => $first_name . ' ' . $last_name,
																)
															);
															update_user_meta( $user_id, 'pwddm_manager', $manager_id );
															update_user_meta( $user_id, 'lddfw_driver_account', '1' );
															update_user_meta( $user_id, 'billing_phone', $phone );
															update_user_meta( $user_id, 'billing_country', $country );
															$result = 1;
															$error  = '<p>' . __( 'New driver successfully created ', 'pwddm' ) . ' <br><br> ' . '<a  href=\'' . pwddm_manager_page_url( 'pwddm_screen=driver&pwddm_driverid=' . $user_id ) . '\'  class=\'btn btn-primary\'>' . __( 'View driver', 'pwddm' ) . '</a></p>';
														} else {
															$error = __( 'An error occurred creating new driver.', 'pwddm' );
														}
													} else {
														$error = __( 'User is already exist in the system.', 'pwddm' );
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
			return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}

	/**
	 *  Get driver address
	 *
	 * @param int $driver_id The driver ID.
	 * @return array
	 */
	public function get_driver_address( $driver_id ) {
		$user_meta = get_userdata( $driver_id );
		$country   = $user_meta->billing_country;
		$city      = $user_meta->billing_city;
		$address_1 = $user_meta->billing_address_1;
		$address_2 = $user_meta->billing_address_2;
		$postcode  = $user_meta->billing_postcode;
		$state     = $user_meta->billing_state;
		if ( '' !== $country ) {
			$country = WC()->countries->countries[ $country ];
		}
		$array = array(
			'street_1' => $address_1,
			'street_2' => $address_2,
			'city'     => $city,
			'zip'      => $postcode,
			'country'  => $country,
			'state'    => $state,
		);

		return array( lddfw_format_address( 'map_address', $array ), lddfw_format_address( 'address_line', $array ) );
	}

}
