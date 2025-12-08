<?php
/**
 * Plugin Name:      MB User Profile
 * Plugin URI:       https://metabox.io/plugins/mb-user-profile/
 * Description:      Register, edit user profiles with custom fields on the front end.
 * Version:          2.5.12
 * Author:           MetaBox.io
 * Author URI:       https://metabox.io
 * License:          GPL2+
 * Text Domain:      mb-user-profile
 * Domain Path:      /languages/
 *
 * Copyright (C) 2010-2025 Tran Ngoc Tuan Anh. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Prevent loading this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'mb_user_profile_load' ) ) {
	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
		require __DIR__ . '/vendor/meta-box/mb-user-meta/mb-user-meta.php';
		require __DIR__ . '/vendor/meta-box/mb-settings-page/mb-settings-page.php';
	}

	/**
	 * Hook to 'init' with priority 4 to make sure all actions are registered before Meta Boxes and MB Settings Page runs.
	 */
	add_action( 'init', 'mb_user_profile_load', 4 );

	/**
	 * Load plugin files after Meta Box is loaded
	 */
	function mb_user_profile_load() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		list( , $url ) = RWMB_Loader::get_path( __DIR__ );
		define( 'MBUP_URL', $url );
		define( 'MBUP_VER', '2.5.12' );
		define( 'MBUP_DIR', __DIR__ );
		define( 'MBUP_DB_VER', 2 );

		load_plugin_textdomain( 'mb-user-profile', false, plugin_basename( __DIR__ ) . '/languages/' );

		add_filter( 'rwmb_admin_menu', '__return_true' );

		new MetaBox\UserProfile\Upgrade;

		new MetaBox\UserProfile\DefaultFields;
		new MetaBox\UserProfile\UserFields;
		new MetaBox\UserProfile\Settings;
		new MetaBox\UserProfile\EmailConfirmation;
		new MetaBox\UserProfile\Shortcodes;
		new MetaBox\UserProfile\Blocks\RegistrationForm;
		new MetaBox\UserProfile\Blocks\LoginForm;
		new MetaBox\UserProfile\Blocks\ProfileForm;
		new MetaBox\UserProfile\Integrations\Elementor\Register;
		new MetaBox\UserProfile\Integrations\Oxygen\Register;
		new MetaBox\UserProfile\Integrations\Bricks\Register;

		// Add dependency notice in AIO.
		if ( class_exists( 'MetaBox\Dependency\Plugins' ) ) {
			new MetaBox\Dependency\Plugins( 'MB User Profile', [
				[
					'name'     => 'MB User Meta',
					'function' => 'mb_user_meta_load',
				],
				[
					'name'     => 'MB Settings Page',
					'function' => 'mb_settings_page_load',
				],
			], [
				// Translators: %1$s - the plugin name, %2$s - extensions, %3$s - action.
				'message'  => __( '%1$s requires %2$s to function correctly. %3$s.', 'mb-user-profile' ),
				'activate' => __( 'Activate now', 'mb-user-profile' ),
			] );
		}
	}
}
