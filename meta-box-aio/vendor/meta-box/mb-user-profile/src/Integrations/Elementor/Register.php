<?php
namespace MetaBox\UserProfile\Integrations\Elementor;

class Register {
	public function __construct() {
		add_action( 'elementor/widgets/register', [ $this, 'register' ] );
		add_action( 'elementor/widget/render_content', [ $this, 'update_data_elementor' ], 10, 2 );
	}

	public function register( $widgets_manager ) {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}
		$widgets_manager->register( new LoginForm() );
		$widgets_manager->register( new RegistrationForm() );
		$widgets_manager->register( new ProfileForm() );
	}

	public function update_data_elementor( $content, $widget ) {
		if ( 'mbup_profile_form' != $widget->get_name() && 'mbup_registration_form' != $widget->get_name() ) {
			return $content;
		}
		$id    = get_the_ID();
		$datas = get_post_meta( $id, '_elementor_data', true );
		$datas = json_decode( $datas, true );
		foreach ( $datas as $key => $data ) {
			foreach ( $data['elements'] as $key2 => $value ) {
				if ( empty( $value['elements'] ) && ! empty( $value['settings']['id'] ) ) {
					$datas[ $key ]['elements'][ $key2 ]['settings']['group_ids'] = $datas[ $key ]['elements'][ $key2 ]['settings']['id'];
					unset( $datas[ $key ]['elements'][ $key2 ]['settings']['id'] );
				}

				if ( ! empty( $value['elements'] ) ) {
					foreach ( $value['elements'] as $key3 => $value ) {
						if ( ! empty( $value['settings']['id'] ) ) {
							$datas[ $key ]['elements'][ $key2 ]['elements'][ $key3 ]['settings']['group_ids'] = $datas[ $key ]['elements'][ $key2 ]['elements'][ $key3 ]['settings']['id'];
							unset( $datas[ $key ]['elements'][ $key2 ]['elements'][ $key3 ]['settings']['id'] );
						}
					}
				}
			}
		}
		update_post_meta( $id, '_elementor_data', json_encode( $datas ) );
		return $content;
	}
}

