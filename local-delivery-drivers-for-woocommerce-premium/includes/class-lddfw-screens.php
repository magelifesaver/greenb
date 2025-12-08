<?php
/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
use Automattic\WooCommerce\Utilities\OrderUtil;
class LDDFW_Screens {

	/**
	 * Footer.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_footer() {
		return "<div id='footer'></div>";
	}

	/**
	 * Header.
	 *
	 * @since 1.0.0
	 * @param string $title page title.
	 * @param string $back_url the url for back.
	 * @return html
	 */
	public function lddfw_header( $title = null, $back_url = null ) {
		global $lddfw_user, $lddfw_driver_availability;

		if ( '1' === $lddfw_driver_availability ) {
			$availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class="lddfw_availability text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';

		} else {
			$availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class="lddfw_availability text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';

		}

		$html = '
            <div id="lddfw_header">
            <div class="container">
                <div class="row">';

		$html .= '<div class="col-2 lddfw_back_column">';
		if ( null !== $back_url ) {
			$html .= '<a href="' . $back_url . '" class="lddfw_back_link lddfw_loader">
			<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-left" class="svg-inline--fa fa-arrow-left fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M257.5 445.1l-22.2 22.2c-9.4 9.4-24.6 9.4-33.9 0L7 273c-9.4-9.4-9.4-24.6 0-33.9L201.4 44.7c9.4-9.4 24.6-9.4 33.9 0l22.2 22.2c9.5 9.5 9.3 25-.4 34.3L136.6 216H424c13.3 0 24 10.7 24 24v32c0 13.3-10.7 24-24 24H136.6l120.5 114.8c9.8 9.3 10 24.8.4 34.3z"></path></svg></a>';
		}

		$html .= '</div>';

		$html .= '<div class="col-8 text-center lddfw_header_title">';
		$html .= $title;
		$html .= '</div>';

		global $lddfw_driver_assigned_status_name, $lddfw_out_for_delivery_status_name, $lddfw_failed_attempt_status_name, $lddfw_out_for_delivery_counter, $lddfw_failed_attempt_counter, $lddfw_delivered_counter, $lddfw_assign_to_driver_counter, $lddfw_claim_orders_counter;

		$driver_photo = '';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				/* driver photo */
				$image_id = get_user_meta( $lddfw_user->ID, 'lddfw_driver_image', true );
				if ( intval( $image_id ) > 0 ) {
					$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
					if ( '' !== $image ) {
						$driver_photo = '<span class="driver_photo_wrap" style="background-image:url(\'' . $image . '\');background-size: cover;"></span>';
					}
				}
			}
		}

		$html .= '<div class="col-2 text-right">
				<a href="#" id="lddfw_menu" onclick="lddfw_openNav()">
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="bars" class="svg-inline--fa fa-bars fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"></path></svg>' . $availability_icon . '
				</a>
				<div id="lddfw_mySidenav" class="lddfw_sidenav">
				<a href="javascript:void(0)" class="lddfw_closebtn" onclick="lddfw_closeNav()">&times;</a>
				<span class="dropdown-header">
					<h3>' . $driver_photo . $lddfw_user->display_name . '</h3>
				</span>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=dashboard' ) . '">
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="home" class="svg-inline--fa fa-home fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M280.37 148.26L96 300.11V464a16 16 0 0 0 16 16l112.06-.29a16 16 0 0 0 15.92-16V368a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v95.64a16 16 0 0 0 16 16.05L464 480a16 16 0 0 0 16-16V300L295.67 148.26a12.19 12.19 0 0 0-15.3 0zM571.6 251.47L488 182.56V44.05a12 12 0 0 0-12-12h-56a12 12 0 0 0-12 12v72.61L318.47 43a48 48 0 0 0-61 0L4.34 251.47a12 12 0 0 0-1.6 16.9l25.5 31A12 12 0 0 0 45.15 301l235.22-193.74a12.19 12.19 0 0 1 15.3 0L530.9 301a12 12 0 0 0 16.9-1.6l25.5-31a12 12 0 0 0-1.7-16.93z"></path></svg> ' . esc_html( __( 'Dashboard', 'lddfw' ) ) . '</a>
				<div class="dropdown-divider"></div>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '1' === get_option( 'lddfw_self_assign_delivery_drivers', '' ) && '1' === get_user_meta( $lddfw_user->ID, 'lddfw_driver_claim', true ) ) {
					$html .= '<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=claim_orders' ) . '">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-right" class="svg-inline--fa fa-angle-double-right fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg> ' . esc_html( __( 'Claim orders', 'lddfw' ) ) . ' (' . $lddfw_claim_orders_counter . ')</a>';
				}
			}
		}

		$html .= '
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=assign_to_driver' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-right" class="svg-inline--fa fa-angle-double-right fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg> ' . $lddfw_driver_assigned_status_name . ' (' . $lddfw_assign_to_driver_counter . ')</a>
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-right" class="svg-inline--fa fa-angle-double-right fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg> ' . $lddfw_out_for_delivery_status_name . ' (' . $lddfw_out_for_delivery_counter . ')</a>
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-right" class="svg-inline--fa fa-angle-double-right fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg> ' . $lddfw_failed_attempt_status_name . ' (' . $lddfw_failed_attempt_counter . ')</a>
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-right" class="svg-inline--fa fa-angle-double-right fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34zm192-34l-136-136c-9.4-9.4-24.6-9.4-33.9 0l-22.6 22.6c-9.4 9.4-9.4 24.6 0 33.9l96.4 96.4-96.4 96.4c-9.4 9.4-9.4 24.6 0 33.9l22.6 22.6c9.4 9.4 24.6 9.4 33.9 0l136-136c9.4-9.2 9.4-24.4 0-33.8z"></path></svg> ' . esc_html( __( 'Delivered', 'lddfw' ) ) . ' (' . $lddfw_delivered_counter . ')</a>
					';

		$html .= '<div class="dropdown-divider"></div>
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" title="' . esc_attr( __( 'Settings', 'lddfw' ) ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=settings' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="cog" class="svg-inline--fa fa-cog fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M487.4 315.7l-42.6-24.6c4.3-23.2 4.3-47 0-70.2l42.6-24.6c4.9-2.8 7.1-8.6 5.5-14-11.1-35.6-30-67.8-54.7-94.6-3.8-4.1-10-5.1-14.8-2.3L380.8 110c-17.9-15.4-38.5-27.3-60.8-35.1V25.8c0-5.6-3.9-10.5-9.4-11.7-36.7-8.2-74.3-7.8-109.2 0-5.5 1.2-9.4 6.1-9.4 11.7V75c-22.2 7.9-42.8 19.8-60.8 35.1L88.7 85.5c-4.9-2.8-11-1.9-14.8 2.3-24.7 26.7-43.6 58.9-54.7 94.6-1.7 5.4.6 11.2 5.5 14L67.3 221c-4.3 23.2-4.3 47 0 70.2l-42.6 24.6c-4.9 2.8-7.1 8.6-5.5 14 11.1 35.6 30 67.8 54.7 94.6 3.8 4.1 10 5.1 14.8 2.3l42.6-24.6c17.9 15.4 38.5 27.3 60.8 35.1v49.2c0 5.6 3.9 10.5 9.4 11.7 36.7 8.2 74.3 7.8 109.2 0 5.5-1.2 9.4-6.1 9.4-11.7v-49.2c22.2-7.9 42.8-19.8 60.8-35.1l42.6 24.6c4.9 2.8 11 1.9 14.8-2.3 24.7-26.7 43.6-58.9 54.7-94.6 1.5-5.5-.7-11.3-5.6-14.1zM256 336c-44.1 0-80-35.9-80-80s35.9-80 80-80 80 35.9 80 80-35.9 80-80 80z"></path></svg> ' . esc_html( __( 'Settings', 'lddfw' ) ) . '</a>
					';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				// Driver pages.
				$array = get_posts(
					array(
						'numberposts' => -1,
						'post_type'   => 'lddfw_driver_pages',
						'post_status' => 'publish',
					)
				);
				if ( ! empty( $array ) ) {
					$html .= '<div class="dropdown-divider"></div>';

					$html .= '<a class="submenu-item" href="#" data="driver_pages_submenu">
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="info-circle" class="svg-inline--fa fa-info-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path></svg> ';

					$html .= __( 'Information', 'lddfw' );

					$html .= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-up" class="svg-inline--fa fa-chevron-up fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg>
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-down" class="svg-inline--fa fa-chevron-down fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg>

				</a>
				<div id="driver_pages_submenu" class="lddfw_submenu">
				';
					foreach ( $array as $page ) {
						$html .= '<a class="dropdown-item lddfw_loader lddfw_loader_fixed" title="' . esc_attr( $page->post_title ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=info&lddfw_page=' . $page->ID ) . '">
								<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="dot-circle" class="svg-inline--fa fa-dot-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 56c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m0-48C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 168c-44.183 0-80 35.817-80 80s35.817 80 80 80 80-35.817 80-80-35.817-80-80-80z"></path></svg>' . $page->post_title . '</a>';
					}
					$html .= '</div>';
				}

				if ( has_filter( 'lddfw_driver_menu' ) ) {
					$html = apply_filters( 'lddfw_driver_menu', $html );
				}
			}
		}

		$html .= '<div class="dropdown-divider"></div>
					<a class="dropdown-item lddfw_loader lddfw_loader_fixed" title="' . esc_attr( __( 'Log out', 'lddfw' ) ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=logout' ) . '">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sign-out-alt" class="svg-inline--fa fa-sign-out-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M497 273L329 441c-15 15-41 4.5-41-17v-96H152c-13.3 0-24-10.7-24-24v-96c0-13.3 10.7-24 24-24h136V88c0-21.4 25.9-32 41-17l168 168c9.3 9.4 9.3 24.6 0 34zM192 436v-40c0-6.6-5.4-12-12-12H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h84c6.6 0 12-5.4 12-12V76c0-6.6-5.4-12-12-12H96c-53 0-96 43-96 96v192c0 53 43 96 96 96h84c6.6 0 12-5.4 12-12z"></path></svg> ' . esc_html( __( 'Log out', 'lddfw' ) ) . '</a>
			</div>
			</div>
		</div>
		</div>
		</div>';
		return $html;
	}

	/**
	 * Homepage.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_home() {
		// show delivery driver homepage.
		global $lddfw_screen, $lddfw_reset_key, $lddfw_reset_login;

		$style_home = '';
		if ( 'resetpassword' === $lddfw_screen ) {
			$style_home = 'style="display:none"';
		}

		// home page.
		$html = '<div class="lddfw_wpage" id="lddfw_home" ' . $style_home . '>
		<div class="container-fluid lddfw_cover"><span class="lddfw_helper"></span>';

		$title    = esc_html( __( 'WELCOME', 'lddfw' ) );
		$subtitle = esc_html( __( 'To delivery drivers manager', 'lddfw' ) );
		$logo     = '<img class="lddfw_header_image" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/lddfw.png?ver=' . LDDFW_VERSION . '">';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				/**
				 * Branding logo, title and subtitle.
				 */
				$lddfw_branding_title    = get_option( 'lddfw_branding_title', '' );
				$lddfw_branding_subtitle = get_option( 'lddfw_branding_subtitle', '' );
				$lddfw_branding_logo     = esc_attr( get_option( 'lddfw_branding_logo', '' ) );

				if ( '' !== $lddfw_branding_logo ) {
					$logo = wp_get_attachment_image_src( $lddfw_branding_logo, 'medium' )[0];
					if ( '' !== $logo ) {
						$logo = '<img src="' . $logo . '">';
					}
				}

				if ( '' !== $lddfw_branding_title ) {
					$title = $lddfw_branding_title;
				}

				if ( '' !== $lddfw_branding_subtitle ) {
					$subtitle = $lddfw_branding_subtitle;
				}
			}
		}

		$html .= $logo;
		$html .= '</div>
		<div class="container">
			<h1>' . $title . '</h1>
			<p>' . $subtitle . '</p>
			<button id="lddfw_start" class="btn btn-primary btn-lg btn-block" type="button">' . esc_html( __( 'Get started', 'lddfw' ) ) . '</button>
		</div>
	</div>
	';

		$login = new LDDFW_Login();
		$html .= $login->lddfw_login_screen();

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$application = new LDDFW_Application();
				$html       .= $application->lddfw_application_screen__premium_only();
				$html       .= $application->lddfw_application_thankyou_screen__premium_only();
			}
		}

		$password = new LDDFW_Password();
		$html    .= $password->lddfw_forgot_password_screen();
		$html    .= $password->lddfw_forgot_password_email_sent_screen();
		$html    .= $password->lddfw_create_password_screen();
		$html    .= $password->lddfw_new_password_created_screen();

		return $html;
	}

	/**
	 * Delivery page.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_out_for_delivery_screen( $driver_id ) {
		global $lddfw_out_for_delivery_status_name, $lddfw_out_for_delivery_counter;
		$orders = new LDDFW_Orders();

		$title    = $lddfw_out_for_delivery_status_name;
		$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$html     = $this->lddfw_header( $title, $back_url );
		$html    .= '<div id="lddfw_content" class="container lddfw_page_content">
            <div class="row">';
		$html    .= '<div class="col-12">';

		if ( lddfw_is_free() ) {
			$button  = esc_attr( __( 'Plan your route', 'lddfw' ) );
			$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Plan your Route by Distance or Manually.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'View your Route on Google Maps.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Navigate with Waze, Apple Maps, or Google Maps.', 'lddfw' ) );
			$html   .= '<div style="margin-bottom:15px;">' . lddfw_premium_feature_notice( $button, $content, '' ) . '</div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$route = new LDDFW_Route();
				if ( $lddfw_out_for_delivery_counter > 0 ) {
					$html .= $route->lddfw_route_alerts__premium_only();
				}
			}
		}

		$html .= '<div id="lddfw_plain_route_container">';
		$html .= $orders->lddfw_out_for_delivery( $driver_id );
		$html .= '</div>
	 				</div>
          		</div>
		  	</div>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$html .= $route->lddfw_route_screen__premium_only();
				if ( $lddfw_out_for_delivery_counter > 0 ) {
					$html .= $route->lddfw_route_button__premium_only();
					$html .= $route->lddfw_route_script__premium_only();
				}
			}
		}

		$html .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Dashboard screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_dashboard_screen( $driver_id ) {
		global $lddfw_driver_assigned_status_name, $lddfw_out_for_delivery_status_name, $lddfw_failed_attempt_status_name, $lddfw_driver_availability, $lddfw_out_for_delivery_counter, $lddfw_failed_attempt_counter, $lddfw_delivered_counter, $lddfw_assign_to_driver_counter, $lddfw_claim_orders_counter;

		$title = __( 'Dashboard', 'lddfw' );
		$html  = $this->lddfw_header( $title );

		$html .= '<div id="lddfw_content" class="container lddfw_dashboard lddfw_page_content">
				<div class="row">
				<div class="col-12">
				<div class="lddfw_box availability">
				<div class="row">
				<div class="col-9 availability-text">' . esc_html( __( 'I am', 'lddfw' ) );

		if ( '1' === $lddfw_driver_availability ) {
			$html .= '
						<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Available', 'lddfw' ) ) . '</span>
						</div>
						<div class="col-3 text-right">
							<a id="lddfw_availability" class="lddfw_active" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg></a>
						</div>
						';
		} else {
			$html .= '
						<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Unavailable', 'lddfw' ) ) . '</span>
						</div>
						<div class="col-3 text-right">
							<a id="lddfw_availability" class="" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg></a>
						</div>';
		}

		$html .= '
			</div>
			</div>
			</div>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
					global $lddfw_drivers_tracking_timing;
				if ( '1' === $lddfw_drivers_tracking_timing ) {
					// Tracking permission.
					$html         .= '
							<div class="col-12">
								<div class="lddfw_box">
									<div class="row">
									<div class="col-9">' . esc_html( __( 'Track Me', 'lddfw' ) ) . '
									</div>';
							$html .= '
										<div class="col-3 text-right">
											<a id="lddfw_trackme" title="' . esc_attr( __( 'Tracking status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
												<span class="lddfw_trackme_on" style="display:none;" >
													<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg>
												</span>
												<span class="lddfw_trackme_off" style="display:none;" >
													<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg>
												</span>
											</a>
										</div>
									';
							$html .= '
									<div class="col-12"><div id="tracking_alert"></div></div>
									</div>
								</div>
							</div>
							';
				}
			}
		}

			// Driver report.
			$report       = new LDDFW_Reports();
			$report_array = $report->lddfw_drivers_commission_query( date_i18n( 'Y-m-d' ), date_i18n( 'Y-m-d' ), $driver_id );
			$commission   = 0;

		if ( ! empty( $report_array ) ) {
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->can_use_premium_code() ) {
					$commission = $report_array[0]->commission;
				}
			}
		}

			$lddfw_driver_commission_permission = get_option( 'lddfw_driver_commission_permission', false );
			$lddfw_driver_commission_permission = false === $lddfw_driver_commission_permission || '1' === $lddfw_driver_commission_permission ? true : false;
		if ( true === $lddfw_driver_commission_permission ) {
			$html .= '<div class = "col-12 "><div class="lddfw_box min">' . esc_html( __( 'Today Earnings', 'lddfw' ) ) . ': ';
			if ( lddfw_is_free() ) {
				$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'View how much money did you make today.', 'lddfw' ) );
				$html   .= lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
			} else {
				$html .= '<b>' . lddfw_premium_feature( wc_price( $commission ) ) . '</b>';
			}
			$html .= '</div></div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '1' === get_option( 'lddfw_self_assign_delivery_drivers', '' ) && '1' === get_user_meta( $driver_id, 'lddfw_driver_claim', true ) ) {
					$html .= '<div class="col-6">
							<div class="lddfw_box min text-center">
							<a class="lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=claim_orders' ) . '">
							<span class="lddfw_number">' . $lddfw_claim_orders_counter . '</span>
							<span class="lddfw_label">' . esc_html( __( 'Claim orders', 'lddfw' ) ) . '</span></a>
							</div>
						</div>';
				}
			}
		}

		$html .= '	<div class="col-6">
				<div class="lddfw_box min text-center">
				<a class="lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=assign_to_driver' ) . '">
				<span class="lddfw_number">' . $lddfw_assign_to_driver_counter . '</span>
				<span class="lddfw_label">' . $lddfw_driver_assigned_status_name . '</span></a>
				</div>
			</div>
			<div class="col-6">
				<div class="lddfw_box min text-center">
				<a class="lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '">
				<span class="lddfw_number">' . $lddfw_out_for_delivery_counter . '</span>
				<span class="lddfw_label">' . $lddfw_out_for_delivery_status_name . '</span></a>
				</div>
			</div>
			<div class="col-6">
				<div class="lddfw_box min text-center">
				<a class="lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' ) . '">
				<span class="lddfw_number">' . $lddfw_failed_attempt_counter . '</span>
				<span class="lddfw_label">' . $lddfw_failed_attempt_status_name . '</span></a>
				</div>
			</div>
			<div class="col-6">
				<div class="lddfw_box min text-center">
				<a class="lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
				<span class="lddfw_number">' . $lddfw_delivered_counter . '</span>
				<span class="lddfw_label">' . esc_html( __( 'Delivered', 'lddfw' ) ) . '</span></a>
				</div>
			</div>
		</div>
		</div>';

		$html .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Failed delivery screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_failed_delivery_screen( $driver_id ) {
		global $lddfw_failed_attempt_status_name;
		$title    = $lddfw_failed_attempt_status_name;
		$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$html     = $this->lddfw_header( $title, $back_url );

		$html .= '<div id="lddfw_content" class="container lddfw_page_content">
		<div class="row">
		<div class="col-12">';

		$orders = new LDDFW_Orders();
		$html  .= $orders->lddfw_failed_delivery( $driver_id );
		$html  .= ' </div>
	  </div>
	</div>';
		$html  .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Driver assigned screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_assign_to_driver_screen( $driver_id ) {
		global $lddfw_driver_assigned_status_name, $lddfw_assign_to_driver_counter;
		$title    = $lddfw_driver_assigned_status_name;
		$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$html     = $this->lddfw_header( $title, $back_url );
		$orders   = new LDDFW_Orders();
		$html    .= '<div id="lddfw_content" class="container lddfw_page_content">
		<div class="row">';

		if ( 0 < $lddfw_assign_to_driver_counter ) {
			$html .= '
			<div class="col-12">
				<h1>' . esc_html( __( 'Mark orders as out for delivery', 'lddfw' ) ) . '</h1>
				<div class="lddfw_subtitle" >' . esc_html( __( 'Choose orders and click on the Out For Delivery button', 'lddfw' ) );
  
					if ( lddfw_is_free() ) {
						$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'View orders details on this screen.', 'lddfw' ) );
						$html   .= ' ' . lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
					}

				$html .= '</div>';
				$html .= '</div>';

				if ( lddfw_fs()->is__premium_only() ) {
					if ( lddfw_fs()->is_plan( 'premium', true ) ) {
						if ( has_filter( 'lddfw_driver_orders_date_time_filter' ) ){	
							$html .= apply_filters( 'lddfw_driver_orders_date_time_filter', '' );
						}
					}
				}

				 
		}

		$html .= '<div class="col-12">
					<div id="lddfw_alert" style="margin-top: 17px; display: none;"></div>
			 	  </div>';

		$html .= '<div class="col-12" id="lddfw_assign_to_driver_orders">';
		$html .= $orders->lddfw_assign_to_driver( $driver_id );
		$html .= ' </div>
	  </div>
	</div>';

		if ( 0 < $lddfw_assign_to_driver_counter ) {

			$html .= '
		<div class="lddfw_footer_buttons">
			<div class="container">
				<div class="row">
					<div class="col-12">
						<a href="#" id="lddfw_out_for_delivery_button" class="btn btn-lg btn-block btn-success"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="truck-loading" class="svg-inline--fa fa-truck-loading fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M50.2 375.6c2.3 8.5 11.1 13.6 19.6 11.3l216.4-58c8.5-2.3 13.6-11.1 11.3-19.6l-49.7-185.5c-2.3-8.5-11.1-13.6-19.6-11.3L151 133.3l24.8 92.7-61.8 16.5-24.8-92.7-77.3 20.7C3.4 172.8-1.7 181.6.6 190.1l49.6 185.5zM384 0c-17.7 0-32 14.3-32 32v323.6L5.9 450c-4.3 1.2-6.8 5.6-5.6 9.8l12.6 46.3c1.2 4.3 5.6 6.8 9.8 5.6l393.7-107.4C418.8 464.1 467.6 512 528 512c61.9 0 112-50.1 112-112V0H384zm144 448c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48z"></path></svg> ' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</a>
						<a href="#" id="lddfw_out_for_delivery_button_loading"  style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-success"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						' . esc_html( __( 'Loading', 'lddfw' ) ) . '</a>
					</div>
				</div>
			</div>
		</div>';
		}
		$html .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Claim orders screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_claim_orders_screen__premium_only( $driver_id ) {
			global $lddfw_claim_orders_counter;
			$title        = __( 'Claim Orders', 'lddfw' );
			$back_url     = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
			$html         = $this->lddfw_header( $title, $back_url );
			$orders       = new LDDFW_Orders();
			$orders_count = $lddfw_claim_orders_counter;
			$html        .= '<div id="lddfw_content" class="container lddfw_page_content">
				<div class="row">';
		if ( 0 < $orders_count ) {
			$html .= '
					<div class="col-12">
						<h1>' . esc_html( __( 'Assign orders to you', 'lddfw' ) ) . '</h1>
						<p>' . esc_html( __( 'Choose orders and click on the Claim Orders button', 'lddfw' ) ) . '</p>
				</div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( has_filter( 'lddfw_driver_orders_date_time_filter' ) ){	
					$html .= apply_filters( 'lddfw_driver_orders_date_time_filter', '' );
				}
			}
		}

			$html .= '<div class="col-12">
					<div id="lddfw_alert" style="margin-top: 17px; display: none;"></div>
			 	  </div>';

		if ( '1' === get_option( 'lddfw_self_assign_delivery_drivers', '' ) && '1' === get_user_meta( $driver_id, 'lddfw_driver_claim', true ) ) {
			$html .= '<div class="col-12">';
			$html .= $orders->lddfw_claim_orders__premium_only( $driver_id );
			$html .= ' </div>';
		}
			$html .= '
			</div>
			</div>';

		if ( 0 < $orders_count ) {

			$html .= '
					<div class="lddfw_footer_buttons">
						<div class="container">
							<div class="row">
								<div class="col-12">
								<a href="#" id="lddfw_claim_orders_button" class="btn btn-lg btn-block btn-success"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="plus" class="svg-inline--fa fa-plus fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"></path></svg> ' . esc_html( __( 'Claim Orders', 'lddfw' ) ) . '</a>
								<a href="#" id="lddfw_claim_orders_button_loading"  style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-success"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
										' . esc_html( __( 'Loading', 'lddfw' ) ) . '</a>
								</div>
							</div>
						</div>
					</div>';
		}
		$html .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Delivered screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_delivered_screen( $driver_id ) {
		$driver_prices_permission           = get_option( 'lddfw_driver_prices_permission', false );
		$lddfw_driver_commission_permission = get_option( 'lddfw_driver_commission_permission', false );
		$driver_prices_permission           = false === $driver_prices_permission || '1' === $driver_prices_permission ? true : false;
		$lddfw_driver_commission_permission = false === $lddfw_driver_commission_permission || '1' === $lddfw_driver_commission_permission ? true : false;

		// This week dates.
		$current_week       = get_weekstartend( date_i18n( 'Y-m-d' ), '' );
		$current_start_week = date( 'Y-m-d', $current_week['start'] );
		$current_end_week   = date( 'Y-m-d', $current_week['end'] );

		// Last week dates.
		$previous_start_week = date( 'Y-m-d', strtotime( $current_start_week . ' -7 day' ) );
		$previous_end_week   = date( 'Y-m-d', strtotime( $current_end_week . ' -7 day' ) );

		global $lddfw_dates;
		$title    = __( 'Delivered', 'lddfw' );
		$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$html     = $this->lddfw_header( $title, $back_url );

		$html .= '<div id="lddfw_content" class="container lddfw_page_content">
		<div class="row">
		<div class="col-12">
		<select class="custom-select form-control custom-select-lg" id="lddfw_dates_range" data="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
		<option value="' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) . '">' . esc_html( __( 'Today', 'lddfw' ) ) . '</option>
		<option value="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '">' . esc_html( __( 'Yesterday', 'lddfw' ) ) . '</option>
		<option value="' . $current_start_week . ',' . $current_end_week . '">' . esc_html( __( 'This week', 'lddfw' ) ) . '</option>
		<option value="' . $previous_start_week . ',' . $previous_end_week . '">' . esc_html( __( 'Last week', 'lddfw' ) ) . '</option>
		<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '">' . esc_html( __( 'This month', 'lddfw' ) ) . '</option>
		<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '">' . esc_html( __( 'Last month', 'lddfw' ) ) . '</option>
		</select>
		<div class="lddfw_date_range">
		';

		if ( '' === $lddfw_dates ) {
			$html     .= date_i18n( lddfw_date_format( 'date' ) );
			$from_date = date_i18n( 'Y-m-d' );
			$to_date   = date_i18n( 'Y-m-d' );
		} else {
			$lddfw_dates_array = explode( ',', $lddfw_dates );
			if ( 1 < count( $lddfw_dates_array ) ) {
				if ( $lddfw_dates_array[0] === $lddfw_dates_array[1] ) {
					$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) );
					$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				} else {
					$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) ) . ' - ' . date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[1] ) );
					$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
				}
			} else {
				$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) );
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
			}
		}
		$html .= '</div>';

		// Driver report.
		$report         = new LDDFW_Reports();
		$report_array   = $report->lddfw_drivers_commission_query( $from_date, $to_date, $driver_id );
		$orders_price   = 0;
		$shipping_price = 0;
		$commission     = 0;
		if ( ! empty( $report_array ) ) {
			$orders_counter = $report_array[0]->orders;
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->can_use_premium_code() ) {
					$orders_price   = $report_array[0]->orders_total;
					$shipping_price = $report_array[0]->shipping_total;
					$commission     = $report_array[0]->commission;
				}
			}
				$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'View how much money did you make, orders total, and shipping total.', 'lddfw' ) );

				$html .= '
				<div class="row delivered-report">
					 <div class = "col-6 col-md-3">
						<div class="lddfw_box min text-center">
							<b class="lddfw_text">' . $orders_counter . '</b>
							<div class="lddfw_break"></div>' . esc_html( __( 'Orders', 'lddfw' ) ) .
						'</div>
					 </div>';

			if ( true === $driver_prices_permission ) {
				$html .= '<div class = "col-6 col-md-3"><div class="lddfw_box min text-center">';
				if ( lddfw_is_free() ) {
							$html .= lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
				} else {
									  $html .= '<b class="lddfw_text">' . lddfw_premium_feature( lddfw_price( $driver_prices_permission, wc_price( $orders_price ) ) ) . '</b>';
				}
								  $html .= '<div class="lddfw_break"></div> ' . esc_html( __( 'Orders Total', 'lddfw' ) ) . '</div>
						</div>
						<div class = "col-6 col-md-3"><div class="lddfw_box min text-center">';
				if ( lddfw_is_free() ) {
					$html .= lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
				} else {
					$html .= '<b class="lddfw_text">' . lddfw_premium_feature( lddfw_price( $driver_prices_permission, wc_price( $shipping_price ) ) ) . '</b>';
				}
								  $html .= '<div class="lddfw_break"></div> ' . esc_html( __( 'Shipping Total', 'lddfw' ) ) . '</div>
						</div>';
			}

			if ( true === $lddfw_driver_commission_permission ) {
				$html .= '<div class = "col-6 col-md-3"><div class="lddfw_box min text-center">';
				if ( lddfw_is_free() ) {
					$html .= lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
				} else {
					$html .= '<b class="lddfw_text">' . lddfw_premium_feature( wc_price( $commission ) ) . '</b>';
				}
				$html .= '<div class="lddfw_break"></div> ' . esc_html( __( 'Commission', 'lddfw' ) ) . '</div>
						</div>';
			}
				$html .= '</div>';
		}

		$orders = new LDDFW_Orders();
		$html  .= $orders->lddfw_delivered( $driver_id );
		$html  .= ' </div>
	  </div>
	</div>';
		$html  .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Order screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_order_screen( $driver_id ) {
		global $lddfw_order_id;
		$order_class             = new LDDFW_Order();
		$orders_class            = new LDDFW_Orders();
		$back_url                = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$order_driverid          = '';
		$title                   = __( 'Order', 'lddfw' );
		$driver_claim_permission = false;
		$order                   = false;

		// Check if the order type is 'shop_order'.
		if ( 'shop_order' !== OrderUtil::get_order_type( $lddfw_order_id ) ) {
			$html  = $this->lddfw_header( $title, $back_url );
			$html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Invalid order type.', 'lddfw' ) ) . '</div>';
			$html .= $this->lddfw_footer();
			return $html;
		}

		// Get the order.
		$order = wc_get_order( $lddfw_order_id );
		if ( ! $order ) {
			$html  = $this->lddfw_header( $title, $back_url );
			$html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Order not found.', 'lddfw' ) ) . '</div>';
			$html .= $this->lddfw_footer();
			return $html;
		}

		// Get order metadata.
		$order_driverid = $order->get_meta( 'lddfw_driverid' );
		$order_status   = $order->get_status();

		// Set the back URL based on order status.
		switch ( 'wc-' . $order_status ) {
			case get_option( 'lddfw_delivered_status' ):
				$back_url = lddfw_drivers_page_url( 'lddfw_screen=delivered' );
				break;
			case get_option( 'lddfw_failed_attempt_status' ):
				$back_url = lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' );
				break;
			case get_option( 'lddfw_out_for_delivery_status' ):
				$back_url = lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' );
				break;
			case get_option( 'lddfw_driver_assigned_status' ):
				$back_url = lddfw_drivers_page_url( 'lddfw_screen=assign_to_driver' );
				break;
		}

		$title = __( 'Order #', 'lddfw' ) . ' ' . $order->get_order_number();

		// Append additional parameters to the back URL if present.
		$back_url = isset( $_GET['lddfw_dates'] ) ? $back_url . '&lddfw_dates=' . sanitize_text_field( wp_unslash( $_GET['lddfw_dates'] ) ) : $back_url;
		$back_url = isset( $_GET['lddfw_page'] ) ? $back_url . '&lddfw_page=' . sanitize_text_field( wp_unslash( $_GET['lddfw_page'] ) ) : $back_url;

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				// Check claim permission.
				$driver_claim_permission = $orders_class->lddfw_claim_orders_permission__premium_only( $driver_id, $order );
			}
		}

		// Generate HTML header.
		$html = $this->lddfw_header( $title, $back_url );

		// Show the order page if the driver has permission.
		if ( $order_driverid !== '' && intval( $order_driverid ) === intval( $driver_id ) || $driver_claim_permission ) {
			$html .= $order_class->lddfw_order_page( $order, $driver_id );
		} else {
			$html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Access Denied, You do not have permissions to access this order', 'lddfw' ) ) . '</div>';
		}

		// Generate HTML footer and return the complete HTML.
		$html .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Edit driver screen.
	 *
	 * @since 1.5.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_driver_settings_screen( $driver_id ) {
		$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
		$title    = esc_html( __( 'Settings', 'lddfw' ) );
		$html     = $this->lddfw_header( $title, $back_url );
		$driver   = new LDDFW_Driver();
		$html    .= $driver->lddfw_edit_driver_form( $driver_id );
		$html    .= $this->lddfw_footer();
		return $html;
	}

	/**
	 * Information screen.
	 *
	 * @since 1.6.7
	 * @return html
	 */
	public function lddfw_info_screen__premium_only() {
		global $lddfw_page, $lddfw_screen;
		if ( 'info' === $lddfw_screen ) {
			$screen   = new LDDFW_Screens();
			$back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
			$title    = __( 'Information', 'lddfw' );
			$html     = $screen->lddfw_header( $title, $back_url );
			$page     = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'lddfw_driver_pages',
					'post_status' => 'publish',
					'post_id'     => $lddfw_page,
				)
			);
			if ( ! empty( $page ) ) {
				$html .= '<div class="container">
					<div class="lddfw_page_content">
					<div class="lddfw_box"><h1 style="margin:0px">' . $page[0]->post_title . '</h1></div>
					<div class="lddfw_box">' . $page[0]->post_content . '</div>
					</div>
					</div>';
			}
			$html .= $screen->lddfw_footer();
		}
		return $html;
	}

}
