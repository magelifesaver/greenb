<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Account_Funds_Widget.
 */
class WC_Account_Funds_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {

		/* translators: Name of the account funds widget */
		parent::__construct( 'widget_account_funds', __( 'My Account Funds', 'woocommerce-account-funds' ) );
	}

	/**
	 * The widget.
	 */
	public function widget( $args, $instance ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget; // phpcs:ignore

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title; // phpcs:ignore
		}

		?>
		<div class="woocommerce woocommerce-account-funds">
			<p>
				<?php

				echo wp_kses_post( sprintf(
					/* translators: Placeholders: %1$s - account funds amount, 2$s - account funds label, e.g. "Account Funds" */
					__( 'You currently have <strong>%1$s</strong> worth of %2$s in your account.', 'woocommerce-account-funds' ),
					WC_Account_Funds::get_account_funds(),
					\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->to_string()
				) );

				?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) . '/' . get_option( 'woocommerce_myaccount_account_funds_endpoint', 'account-funds' ) ); ?>">
					<?php
					/* translators: Account funds deposits */
					esc_html_e( 'View deposits', 'woocommerce-account-funds' );
					?>
				</a>
			</p>
		</div>
		<?php

		echo $after_widget; // phpcs:ignore
	}

	/**
	 * Update settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = [];
		$instance['title'] = wc_clean( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Settings forms.
	 */
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = sprintf( /* translators: Placeholder: %s - label used to describe account funds owned by the current user, e.g. "My account funds" */
				__( 'My %s', 'woocommerce-account-funds' ), \Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->to_string() );
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'woocommerce-account-funds' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

}

register_widget( 'WC_Account_Funds_Widget' );
