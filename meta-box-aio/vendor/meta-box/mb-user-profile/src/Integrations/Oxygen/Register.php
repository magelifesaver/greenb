<?php
namespace MetaBox\UserProfile\Integrations\Oxygen;

class Register {
	public function __construct() {
		if ( ! defined( 'CT_VERSION' ) ) {
			return;
		}
		add_action( 'oxygen_add_plus_meta-box_section_content', [ $this, 'register_add_plus_subsections' ] );
		new LoginForm();
		new RegistrationForm();
		new ProfileForm();
	}

		// sub section add
	public function register_add_plus_subsections() {
		?><h2><?php esc_html_e( 'User profile', 'mb-user-profile' ); ?></h2>
		<?php
		do_action( 'oxygen_add_plus_meta-box_mb-user-profile' );
	}
}

