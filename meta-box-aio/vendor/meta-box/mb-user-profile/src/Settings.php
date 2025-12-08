<?php
namespace MetaBox\UserProfile;

class Settings {
	public function __construct() {
		add_filter( 'mb_settings_pages', [ $this, 'register_settings_page' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'register_settings_fields' ] );
	}

	public function register_settings_page( $settings_pages ) {
		$settings_pages[] = [
			'menu_title' => __( 'User Profile', 'mb-user-profile' ),
			'id'         => 'user-profile',
			'position'   => 59,
			'parent'     => 'meta-box',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'columns'    => 1,
		];

		return $settings_pages;
	}

	public function register_settings_fields( $meta_boxes ) {
		$meta_boxes[] = array(
			'title'          => __( 'Email confirmation', 'mb-user-profile' ),
			'id'             => 'mbup-email-confirmation',
			'settings_pages' => [ 'user-profile' ],
			'fields'         => [
				[
					'name' => __( 'Force password change?', 'mb-user-profile' ),
					'id'   => 'force_password_change',
					'type' => 'switch',
					'desc' => __( 'Force users to change the passwords for the first login', 'mb-user-profile' ),
				],
				[
					'type' => 'heading',
					'name' => __( 'Email confirmation', 'mb-user-profile' ),
				],
				[
					'name'      => __( 'Success page', 'mb-user-profile' ),
					'id'        => 'email_confirmation_success_page',
					'type'      => 'post',
					'post_type' => [ 'page' ],
					'desc'      => __( 'Select a page to show when users successfully confirm their email. If no page is selected, a default message will be displayed.', 'mb-user-profile' ),
				],
				[
					'name'      => __( 'Error page', 'mb-user-profile' ),
					'id'        => 'email_confirmation_error_page',
					'type'      => 'post',
					'post_type' => [ 'page' ],
					'desc'      => __( 'Select a page to show when there are errors when users confirming their email. If no page is selected, a default message will be displayed.', 'mb-user-profile' ),
				],
			],
		);
		return $meta_boxes;
	}

	public static function get( string $name ) {
		return rwmb_meta( $name, [ 'object_type' => 'setting' ], 'user-profile' );
	}
}
