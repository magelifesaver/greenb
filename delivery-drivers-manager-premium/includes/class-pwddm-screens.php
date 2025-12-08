<?php
/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Screens {

	/**
	 * Footer.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_footer() {
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
	public function pwddm_header( $title = null, $back_url = null ) {
		global $pwddm_manager_id,  $pwddm_manager_drivers;
		$html = '
		<div class="container-fluid fixed-top header-container" >
		<div class="container">
		<div class="row">
		<div class="col-12">

		<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<a class="navbar-brand" href="' . pwddm_manager_page_url( 'pwddm_screen=dashboard' ) . '">' . get_bloginfo( 'name' ) . '</a>
		
		
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		  <span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarSupportedContent">
		  <ul class="navbar-nav mr-auto">
			<li class="nav-item">
			  <a class="nav-link" href="' . pwddm_manager_page_url( 'pwddm_screen=dashboard' ) . '">' . esc_html( __( 'Dashboard', 'pwddm' ) ) . '</a>
			</li>
			<li class="nav-item">
			  <a class="nav-link" href="' . pwddm_manager_page_url( 'pwddm_screen=reports' ) . '">' . esc_html( __( 'Reports', 'pwddm' ) ) . '</a>
			</li>

			<li class="nav-item">
			  <a class="nav-link" href="' . pwddm_manager_page_url( 'pwddm_screen=routes' ) . '">' . esc_html( __( 'Routes', 'pwddm' ) ) . '</a>
			</li>

		  <li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			  ' . esc_html( __( 'Drivers', 'pwddm' ) ) . '
			</a>
			<div class="dropdown-menu" aria-labelledby="navbarDropdown">';
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				if ( '1' === $pwddm_manager_drivers || '2' === $pwddm_manager_drivers ) {
					$html .= ' <a class="dropdown-item" href="' . pwddm_manager_page_url( 'pwddm_screen=newdriver' ) . '">' . esc_html( __( 'Add new driver', 'pwddm' ) ) . '</a>';
				}
			}
		}

			$html .= '<a class="dropdown-item" href="' . pwddm_manager_page_url( 'pwddm_screen=drivers' ) . '">' . esc_html( __( 'All drivers', 'pwddm' ) ) . '</a>
			</div>
		  </li>

		  <li class="nav-item dropdown">
		  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			' . esc_html( __( 'Orders', 'pwddm' ) ) . '
		  </a>
		  <div class="dropdown-menu" aria-labelledby="navbarDropdown">
			<a class="dropdown-item" href="' . pwddm_manager_page_url( 'pwddm_screen=orders' ) . '">' . esc_html( __( 'All orders', 'pwddm' ) ) . '</a>
		  </div>
		</li>

		 <li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
			  ' . esc_html( __( 'Account', 'pwddm' ) ) . '
			</a>
			<div class="dropdown-menu" aria-labelledby="navbarDropdown">
			<!-- 
				<a class="dropdown-item" href="' . pwddm_manager_page_url( 'pwddm_screen=settings' ) . '">' . esc_html( __( 'Settings', 'pwddm' ) ) . '</a>
				<div class="dropdown-divider"></div>
			-->
			  <a class="dropdown-item" href="' . pwddm_manager_page_url( 'pwddm_screen=logout' ) . '">' . esc_html( __( 'Log out', 'pwddm' ) ) . '</a>
			</div>
		  </li>

		  </ul>

		</div>
	  </nav>
	  </div>
		</div>
	 </div>
	 </div>';
		if ( '' !== $title ) {
			$html .= '<div class="container">
		<div class="row">
			<div class="col-12">
				<h1>' . $title . '</h1>
			</div>
		</div>
	 </div>';
		}

		return $html;
	}

	/**
	 * Homepage.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_home() {
		// show manager homepage.
		global $pwddm_screen, $pwddm_reset_key, $pwddm_reset_login;

		$style_home = '';
		if ( 'resetpassword' === $pwddm_screen ) {
			$style_home = 'style="display:none"';
		}

		// home page.
		$html = '<div class="pwddm_wpage" id="pwddm_home" ' . $style_home . '>
		<div class="container-fluid pwddm_cover"><span class="pwddm_helper"></span>';

		$title    = esc_html( __( 'WELCOME', 'pwddm' ) );
		$subtitle = esc_html( __( 'To the delivery manager', 'pwddm' ) );
		$logo     = '<img class="pwddm_header_image" src="' . plugins_url() . '/' . PWDDM_FOLDER . '/public/images/pwddm.png?ver=' . PWDDM_VERSION . '">';

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {

				/**
				 * Branding logo, title and subtitle.
				 */
				$pwddm_branding_title    = get_option( 'pwddm_branding_title', '' );
				$pwddm_branding_subtitle = get_option( 'pwddm_branding_subtitle', '' );
				$pwddm_branding_logo     = esc_attr( get_option( 'pwddm_branding_logo', '' ) );

				if ( '' !== $pwddm_branding_logo ) {
					$logo = wp_get_attachment_image_src( $pwddm_branding_logo, 'medium' )[0];
					if ( '' !== $logo ) {
						$logo = '<img src="' . $logo . '">';
					}
				}

				if ( '' !== $pwddm_branding_title ) {
					$title = $pwddm_branding_title;
				}

				if ( '' !== $pwddm_branding_subtitle ) {
					$subtitle = $pwddm_branding_subtitle;
				}
			}
		}

		$html .= $logo;
		$html .= '</div>
		<div class="container">
			<h1>' . $title . '</h1>
			<p>' . $subtitle . '</p>
			<div class="d-grid gap-2 col-12 col-md-6 mx-auto">
			<button id="pwddm_start" class="btn btn-primary btn-lg btn-block" type="button">' . esc_html( __( 'Get started', 'pwddm' ) ) . '</button>
			</div>
		</div>
	</div>
	';

		$login = new PWDDM_Login();
		$html .= $login->pwddm_login_screen();

		$password = new PWDDM_Password();
		$html    .= $password->pwddm_forgot_password_screen();
		$html    .= $password->pwddm_forgot_password_email_sent_screen();
		$html    .= $password->pwddm_create_password_screen();
		$html    .= $password->pwddm_new_password_created_screen();

		return $html;
	}


	/**
	 * New driver screen.
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_new_driver_screen( $manager_id ) {
		$title  = esc_html( __( 'New Driver', 'pwddm' ) );
		$html   = $this->pwddm_header( $title );
		$driver = new PWDDM_Driver();
		$html  .= $driver->pwddm_new_driver_form();
		$html  .= $this->pwddm_footer();
		return $html;
	}

	/**
	 * Edit driver screen.
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_driver_screen( $manager_id, $driver_id ) {
		$user_meta = get_userdata( $driver_id );
		$title     = esc_html( $user_meta->first_name . ' ' . $user_meta->last_name, 'pwddm' );
		$html      = $this->pwddm_header( $title );
		$driver    = new PWDDM_Driver();
		$html     .= $driver->pwddm_edit_driver_form( $user_meta );
		$html     .= $this->pwddm_footer();
		return $html;
	}

	/**
	 * All drivers screen.
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_drivers_screen( $manager_id ) {
		$title  = esc_html( __( 'Drivers', 'pwddm' ) );
		$html   = $this->pwddm_header( $title );
		$driver = new PWDDM_Driver();
		$html  .= $driver->pwddm_drivers( $manager_id );
		$html  .= $this->pwddm_footer();
		return $html;
	}



	/**
	 * Dashboard screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_dashboard_screen( $driver_id ) {
		$title   = __( 'Dashboard', 'pwddm' );
		$html    = $this->pwddm_header( $title );
		$reports = new PWDDM_Reports();
		$html   .= $reports->screen_dashboard();
		$html   .= $this->pwddm_footer();
		return $html;
	}

	/**
	 * Reports screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_reports_screen( $driver_id ) {
		$title   = __( 'Reports', 'pwddm' );
		$html    = $this->pwddm_header( $title );
		$reports = new PWDDM_Reports();
		$html   .= $reports->screen_reports();
		$html   .= $this->pwddm_footer();
		return $html;
	}

	/**
	 * Drivers routes.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_routes_screen( $driver_id ) {
		// $title = __( 'Drivers routes', 'pwddm' );
		echo $this->pwddm_header( '' );

		if ( pwddm_is_free() ) {
			$content = pwddm_premium_feature( '' ) . ' ' . esc_html( __( "View drivers' routes on a map.", 'pwddm' ) ) . '<br>
					 <p>' . pwddm_premium_feature( '' ) . ' ' . esc_html( __( "View routes' duration and distance.", 'pwddm' ) ) . '</p>
					<p>
					<img style="max-width:100%" src="' . plugins_url() . '/' . PWDDM_FOLDER . '/public/images/routes-preview.png?ver=' . PWDDM_VERSION . '"></p>
					';
			echo '<div class="container">' . pwddm_premium_feature_notice_content( $content ) . '</div>';
		}

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {
				$route = new PWDDM_route();
				echo $route->pwddm_routes_screen();
			}
		}

		echo $this->pwddm_footer();

	}

	/**
	 * Manager settings.
	 *
	 * @since 1.0.0
	 * @param int $seller_id seller user id.
	 * @return html
	 */
	public function pwddm_settings_screen( $seller_id ) {
		$title   = __( 'Settings', 'pwddm' );
		$html    = $this->pwddm_header( $title );
		$manager = new PWDDM_Manger();
		$html   .= $manager->pwddm_settings_form( $seller_id );
		$html   .= $this->pwddm_footer();
		return $html;
	}









	/**
	 * Orders screen.
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_orders_screen( $manager_id ) {
		$title    = __( 'Orders', 'pwddm' );
		$back_url = pwddm_manager_page_url( 'pwddm_screen=dashboard' );
		$html     = $this->pwddm_header( $title, $back_url );
		$html    .= '<div id="pwddm_content" class="container">';
		$orders   = new PWDDM_Orders();
		$html    .= $orders->pwddm_orders_page( $manager_id );
		$html    .= ' </div>';
		$html    .= $this->pwddm_footer();
		return $html;
	}

	/**
	 * Order screen.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_order_screen( $manager_id ) {
		global $pwddm_order_id;
		$order_class = new PWDDM_Order();
		$order       = wc_get_order( $pwddm_order_id );
		$title       = __( 'Order #', 'pwddm' ) . ' ' . $order->get_order_number();
		$html        = $this->pwddm_header( $title, '' );
		$html       .= $order_class->pwddm_order_page( $order, $manager_id );
		$html       .= $this->pwddm_footer();
		return $html;
	}
}
